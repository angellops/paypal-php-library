document.addEventListener("DOMContentLoaded", function () {
    const venmoButtonContainer = document.getElementById("venmo-container");
    if ( !venmoButtonContainer ) return;

    const checkoutData = JSON.parse(venmoButtonContainer.dataset.checkout);
    let selectedPaymentSource = null;

    async function createOrder(paymentSource) {
        try {
            const items = checkoutData.venmo_items.map(item => ({
                name: item.name,
                sku: item.id,
                unit_amount: {
                    currency_code: "USD",
                    value: item.price
                },
                quantity: item.qty,
            }));

            const orderPayload = {
                intent: "CAPTURE",
                purchase_units: [
                    {
                        items: items,
                        amount: {
                            currency_code: "USD",
                            value: checkoutData.grand_total,
                            breakdown: {
                                item_total: {
                                    currency_code: "USD",
                                    value: checkoutData.subtotal,
                                }
                            },
                        },
                    },
                ],
                payment_source: {
                    [paymentSource]: {
                        experience_context: {
                            brand_name: "AngellEYE",
                            shipping_preference: "GET_FROM_FILE"
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
                console.log(data.message || "Unable to create PayPal order.");
                return;
            }

            return { orderId: data?.order_id };
        } catch (error) {
            showPaypalError(error.message);
            throw error;
        }
    }

    function showPaypalError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.textContent = message;
        venmoButtonContainer.appendChild(errorDiv);
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
                window.location.href = `getOrder.php?payment_source=${data.paymentSource}&order_id=${data.orderId}`;
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
                    paymentSource: selectedPaymentSource
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
                components: ["paypal-payments", "venmo-payments"],
            });

            const paymentMethods = await sdkInstance.findEligibleMethods({
                currencyCode: "USD",
                paymentFlow: "VAULT_WITH_PAYMENT",
            });

            const isPayPalEligible = paymentMethods.isEligible("paypal");
            if (isPayPalEligible) {
                setupPayPalButton(sdkInstance);
            }

            const isVenmoEligible = paymentMethods.isEligible("venmo");
            if (isVenmoEligible) {
                setupVenmoButton(sdkInstance);
            }
        } catch (error) {
            showPaypalError(`Initialization error: ${error.message}`);
            throw error;
        }
    }

    async function setupPayPalButton(sdkInstance) {
        const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(
            paymentSessionOptions,
        );

        // Get reference to the Venmo button element
        const paypalButton = document.querySelector("#paypal-button");
        
        // Show the button since Venmo is eligible
        paypalButton.removeAttribute("hidden");

        paypalButton.addEventListener("click", async () => {
            try {
                selectedPaymentSource = "paypal";

                // Start the payment session
                await paypalPaymentSession.start({ 
                        presentationMode: "auto" 
                    },
                    createOrder('paypal')
                );
            } catch (error) {
                showPaypalError(`Error: ${error.message}`);
                throw error;
            }
        });
    }

    async function setupVenmoButton(sdkInstance) {
        const venmoPaymentSession = sdkInstance.createVenmoOneTimePaymentSession(
            paymentSessionOptions,
        );

        // Get reference to the Venmo button element
        const venmoButton = document.querySelector("#venmo-button");
        
        // Show the button since Venmo is eligible
        venmoButton.removeAttribute("hidden");

        venmoButton.addEventListener("click", async () => {
            try {
                selectedPaymentSource = "venmo";

                await venmoPaymentSession.start({ 
                        presentationMode: "auto" 
                    },
                    createOrder('venmo')
                );
            } catch (error) {
                showPaypalError(`Error: ${error.message}`);
                throw error;
            }
        });
    }

    init();
});