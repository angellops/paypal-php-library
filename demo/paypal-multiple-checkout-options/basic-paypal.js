document.addEventListener('DOMContentLoaded', function () {
    // Initialize PayPal Pay Later Messages on load
    initPayPal();

    // CreateOrder and PayLater
    let checkoutData = null;
    const payLaterBtnContainer = document.getElementById("paypal-container");
    if ( payLaterBtnContainer !== undefined && payLaterBtnContainer !== null ) {
        checkoutData = JSON.parse(payLaterBtnContainer.dataset.checkout);
    }

    async function createOrder() {
        try {
            const items = checkoutData.checkout_options_items.map(item => ({
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
                payment_method: {
                    payer_selected: "PAYPAL"
                },
                purchase_units: [
                    {
                        reference_id: "ORDER-" + Date.now(),
                        invoice_id: "INV-" + Date.now(),
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
                                    value: Number(checkoutData.shipping).toFixed(2),
                                },
                                handling: {
                                    currency_code: "USD",
                                    value: Number(checkoutData.handling).toFixed(2),
                                },
                                tax_total: {
                                    currency_code: "USD",
                                    value: Number(checkoutData.tax).toFixed(2),
                                }
                            },
                        },
                        items: items,
                    },
                ],
                payment_source: {
                    paypal: {
                        experience_context: {
                            brand_name: "AngellEYE",
                            shipping_preference: "GET_FROM_FILE",
                            user_action: "PAY_NOW"
                        }
                    }
                }
            };

            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(orderPayload)
            })

            const data = await response.json();
            if (!data.order_id) {
                showPaypalError("Unable to create PayPal order.");
                return;
            }

            return { orderId: data?.order_id };
        } catch (error) {
            showPaypalError(error.message);
            throw error;
        }
    }

    function showPaypalError(message) {
        const payPalContainer = document.getElementById('paypal-container');
        if( payPalContainer !== undefined && payPalContainer !== null ) {
            const errorContainer = payPalContainer.querySelector('#paypalError');
            if( errorContainer !== undefined && errorContainer !== null ) {
                errorContainer.style.display = "block";
                errorContainer.innerHTML = message;
            }
        }
    }

    async function captureOrder(data) {
        try {
            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_capture_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: data.orderId }),
            });

            const captureResult = await response.json();

            if (captureResult.status === "COMPLETED") {
                window.location.href = `getOrder.php?payment_mode=paypal&order_id=${data.orderId}`;
            } else {
                throw new Error("Payment capture failed.");
            }

        } catch (error) {
            showPaypalError(error.message);
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
                showPaypalError(error.message);
                throw error;
            }
        },
        onCancel() {
            location.reload();
        },
        onError(error) {
            showPaypalError(error.message);
        },
    };

    /**
     * PAYPAL V6 SDK INITIALIZATION
     * Fetches a client token and sets up the financing messages.
     */
    async function initPayPal() {
        try {
            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();

            const sdkInstance = await paypal.createInstance({
                clientToken: token,
                components: ["paypal-payments"],
                pageType: 'checkout',
            });

            const paymentMethods = await sdkInstance.findEligibleMethods({
                currencyCode: "USD",
            });

            if (paymentMethods.isEligible("paypal")) {
                setUpPayLaterButton(sdkInstance);
            }
        } catch (error) {
            console.error("PayPal Message Init Error:", error);
        }
    }

    // Set up standard PayPal button
    async function setUpPayLaterButton(sdkInstance, paymentMethods) {
        const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(
            paymentSessionOptions,
        );

        const payPalButton = document.querySelector("paypal-button");
        if ( !payPalButton ) return;

        payPalButton.removeAttribute("hidden");
        payPalButton.addEventListener("click", async () => {
            try {
                await paypalPaymentSession.start(
                    { presentationMode: "auto" },
                    createOrder(),
                );
            } catch (error) {
                showPaypalError(`PayPal payment start error: ${error}`)
            }
        });
    }
});