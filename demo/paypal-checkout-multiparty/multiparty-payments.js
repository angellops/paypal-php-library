document.addEventListener("DOMContentLoaded", function () {
    const payPalBtnContainer = document.getElementById("paypal-button-container");
    if ( !payPalBtnContainer ) return;

    const checkoutData = JSON.parse(payPalBtnContainer.dataset.checkout);

    async function createOrder() {
        try {
            const purchaseUnits = checkoutData.multiparty_items.map((item, index) => {
                const itemTotal = (Number(item.price) * Number(item.qty)).toFixed(2);
                return {
                    reference_id: `CART-${index}`,
                    amount: {
                        currency_code: "USD",
                        value: itemTotal,
                        breakdown: {
                            item_total: {
                                currency_code: "USD",
                                value: itemTotal
                            },
                            shipping: {
                                currency_code: "USD",
                                value: "0.00"
                            },
                            handling: {
                                currency_code: "USD",
                                value: "0.00"
                            },
                            tax_total: {
                                currency_code: "USD",
                                value: "0.00"
                            }
                        }
                    },
                    items: [
                        {
                            name: item.name,
                            sku: item.id,
                            quantity: item.qty,
                            unit_amount: {
                                currency_code: "USD",
                                value: Number(item.price).toFixed(2)
                            }
                        }
                    ],
                    payee: {
                        merchant_id: item.seller_id
                    }
                };

                });

                const orderPayload = {
                    intent: "CAPTURE",
                    purchase_units: purchaseUnits,
                    payer: {
                        email_address: checkoutData.buyer_email
                    },
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
        const errorContainer = document.getElementById('paypalError');
        if( !errorContainer ) return;
        errorContainer.style.display = "block";
        errorContainer.innerHTML = message;
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
                window.location.href = `getOrder.php?order_id=${data.orderId}`;
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

    async function init() {
        try {
            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
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
            }
        } catch (error) {
            showPaypalError(`Initialization error: ${error.message}`);
            throw error;
        }
    }

    // Set up standard PayPal button
    async function setUpPayPalButton(sdkInstance) {
        const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(
            paymentSessionOptions,
        );

        const paypalButton = document.querySelector("paypal-button");
        paypalButton.removeAttribute("hidden");

        paypalButton.addEventListener("click", async () => {
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

    init();
});