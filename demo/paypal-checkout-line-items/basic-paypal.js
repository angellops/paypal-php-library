document.addEventListener("DOMContentLoaded", function () {
    const payPalBtnContainer = document.getElementById("paypal-button-container");
    if ( !payPalBtnContainer ) return;

    const checkoutData = JSON.parse(payPalBtnContainer.dataset.checkout);

    async function createOrder() {
        try {
            const items = checkoutData.line_items.map(item => ({
                name: item.name,
                sku: item.id,
                unit_amount: {
                    currency_code: "USD",
                    value: Number(item.price).toFixed(2)
                },
                quantity: item.qty.toString(),
                category: item.category
            }));

            const orderPayload = {
                intent: "CAPTURE",
                purchase_units: [
                    {
                        amount: {
                            currency_code: "USD",
                            value: Number(checkoutData.grand_total).toFixed(2),
                            breakdown: {
                                item_total: {
                                    currency_code: "USD",
                                    value: Number(checkoutData.subtotal).toFixed(2),
                                },
                            },
                        },
                        items: items,
                    },
                ],
                payment_source: {
                    paypal: {
                        experience_context: {
                            brand_name: "AngellEYE",
                            shipping_preference: "NO_SHIPPING",
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