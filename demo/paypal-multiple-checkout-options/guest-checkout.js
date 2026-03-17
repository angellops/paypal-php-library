document.addEventListener("DOMContentLoaded", function () {
    const guestCheckoutContainer = document.getElementById("guest-checkout-container");
    if ( !guestCheckoutContainer ) return;

    const checkoutData = JSON.parse(guestCheckoutContainer.dataset.checkout);

    async function createOrder() {
        try {
            const items = checkoutData.checkout_options_items.map(item => ({
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
                                },
                                shipping: {
                                    currency_code: "USD",
                                    value: checkoutData.shipping
                                },
                                handling: {
                                    currency_code: "USD",
                                    value: checkoutData.handling
                                },
                                tax_total: {
                                    currency_code: "USD",
                                    value: checkoutData.tax
                                }
                            },
                        },
                    },
                ]
            };

            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(orderPayload)
            })

            const data = await response.json();
            if (!data.order_id) {
                throw new Error(data.message || "Unable to create PayPal order.");
            }

            return data.order_id;
        } catch (error) {
            showPaypalError(error.message);
            throw error;
        }
    }

    function showPaypalError(message) {
        guestCheckoutContainer.innerHTML = message;
    }

    async function onApprove(data) {
        try {
            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_capture_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: data.orderId }),
            });

            const captureResult = await response.json();

            if (captureResult.status === "COMPLETED") {
                window.location.href = `getOrder.php?payment_mode=guest&order_id=${data.orderId}`;
            } else {
                throw new Error("Payment capture failed.");
            }

        } catch (error) {
            showPaypalError(error.message);
            throw error;
        }
    }

    function onCancel(data) {
        location.reload();
    }

    function onComplete(data) {
        console.log(`Guest checkout completed: ${JSON.stringify(data, null, 2)}`);
    }

    function onError(error) {
        showPaypalError(`Payment error: ${JSON.stringify(error)}`);
    }

    async function init() {
        try {
            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();

            const sdkInstance = await window.paypal.createInstance({
                clientToken: token,
                components: ["paypal-guest-payments"],
            });

            document.querySelector("#paypal-basic-card-button").removeAttribute("hidden");

            const guestCheckoutSession = sdkInstance.createPayPalGuestOneTimePaymentSession({
                onApprove, onCancel, onComplete, onError,
            });

            async function onGuestCheckoutButtonClick() {
                try {
                    const checkoutOptionsPromise = createOrder().then((id) => ({orderId: id}));

                    await guestCheckoutSession.start(
                        { presentationMode: "auto" },
                        checkoutOptionsPromise
                    );
                } catch (error) {
                    showPaypalError(`Error starting Guest checkout: ${error.message}`);
                    throw error;
                }
            }
            document.querySelector("#paypal-basic-card-button").addEventListener("click", onGuestCheckoutButtonClick);
        } catch (error) {
            showPaypalError(`Initialization error: ${error.message}`);
            throw error;
        }
    }

    init();
});