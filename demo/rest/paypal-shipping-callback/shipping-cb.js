document.addEventListener("DOMContentLoaded", function () {
    const payPalBtnContainer = document.getElementById("paypal-button-container");
    if ( payPalBtnContainer !== undefined && payPalBtnContainer !== null ) {
        const checkoutData = JSON.parse(payPalBtnContainer.dataset.checkout);

        async function createOrder() {
            try {
                const items = checkoutData.callback_items.map(item => ({
                    name: item.name,
                    sku: item.id,
                    unit_amount: {
                        currency_code: "USD",
                        value: Number(item.price).toFixed(2)
                    },
                    quantity: item.qty.toString(),
                }));

                const orderPayload = {
                    intent: "CAPTURE",
                    purchase_units: [
                        {
                            reference_id: "ORDER-" + Date.now(),
                            items: items,
                            amount: {
                                currency_code: "USD",
                                value: Number(checkoutData.grand_total).toFixed(2),
                                breakdown: {
                                    item_total: {
                                        currency_code: "USD",
                                        value: Number(checkoutData.subtotal).toFixed(2),
                                    },
                                    shipping: {
                                        currency_code: "USD",
                                        value: Number(checkoutData.shipping).toFixed(2)
                                    },
                                    handling: {
                                        currency_code: "USD",
                                        value: Number(checkoutData.handling).toFixed(2)
                                    },
                                    tax_total: {
                                        currency_code: "USD",
                                        value: Number(checkoutData.tax).toFixed(2)
                                    }
                                },
                            },
                        },
                    ],
                    payment_source: {
                        paypal: {
                            experience_context: {
                                brand_name: "AngellEYE",
                                shipping_preference: "GET_FROM_FILE",
                                order_update_callback_config: {
                                    callback_url: checkoutData.shipping_callback_url,
                                    callback_events: ['SHIPPING_ADDRESS'],
                                }
                            }
                        }
                    }
                };

                const response = await fetch('../../core/paypal-api.php?action=ae_create_order', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(orderPayload)
                })

                const data = await response.json();
                if (!data.order_id) {
                    showPaypalMessage("Unable to create PayPal order.", "error");
                    return;
                }

                return { orderId: data?.order_id };
            } catch (error) {
                showPaypalMessage(error.message, "error");
                throw error;
            }
        }

        // Show PayPal message
        function showPaypalMessage(message, type = 'info') {
            const container = document.getElementById('paypalMessage');
            if (!container) return;

            // Reset classes
            container.className = 'paypal-message';

            // Add type class (info / error)
            container.classList.add(type);

            container.style.display = 'block';
            container.innerHTML = message;
        }

        // Hide PayPal message
        function hidePaypalMessage() {
            const container = document.getElementById('paypalMessage');
            if (!container) return;

            container.style.display = 'none';
            container.innerHTML = '';
        }

        async function captureOrder(data) {
            try {
                const response = await fetch('../../core/paypal-api.php?action=ae_capture_order', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: data.orderId }),
                });

                const captureResult = await response.json();

                if (captureResult.status === "COMPLETED") {
                    window.location.href = `getOrder.php?order_id=${data.orderId}`;
                } else {
                    throw new Error("Payment capture failed.");
                }

            } catch (error) {
                showPaypalMessage(error.message, "error");
                throw error;
            }
        }

        const paymentSessionOptions = {
            async onApprove(data) {
                try {
                    const orderData = await captureOrder({
                        orderId: data.orderId,
                    });
                } catch (error) {
                    showPaypalMessage(error.message, "error");
                    throw error;
                }
            },
            onCancel() {
                location.reload();
            },
            onError(error) {
                showPaypalMessage(error.message, "error");
            },
        };

        async function init() {
            try {
                showPaypalMessage('Setting up PayPal Button, please wait...', 'info');

                const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
                const { token } = await res.json();

                const sdkInstance = await window.paypal.createInstance({
                    clientToken: token,
                    components: ['paypal-payments'],
                    pageType: 'checkout'
                });

                const paymentMethods = await sdkInstance.findEligibleMethods({
                    currencyCode: "USD",
                });

                if (paymentMethods.isEligible("paypal")) {
                    setUpPayPalButton(sdkInstance);
                    hidePaypalMessage();
                }
            } catch (error) {
                showPaypalMessage(`Initialization error: ${error.message}`, 'error');
                throw error;
            }
        }

        // Set up standard PayPal button
        async function setUpPayPalButton(sdkInstance) {
            const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(
                paymentSessionOptions,
            );

            const paypalButton = document.getElementById("paypalbtn");
            paypalButton.removeAttribute("hidden");

            paypalButton.addEventListener("click", async () => {
                try {
                    await paypalPaymentSession.start(
                        { presentationMode: "auto" },
                        createOrder(),
                    );
                } catch (error) {
                    showPaypalMessage(`PayPal payment start error: ${error}`, "error")
                }
            });
        }

        init();
    }
});