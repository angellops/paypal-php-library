document.addEventListener("DOMContentLoaded", function () {
    const guestCheckoutContainer = document.getElementById("guest-checkout-container");
    if ( !guestCheckoutContainer ) return;

    const checkoutData = JSON.parse(guestCheckoutContainer.dataset.checkout);

    async function createOrder() {
        try {
            const cart = checkoutData.cart;
            const billing = checkoutData.billing;
            const payer = checkoutData.payer;

            const items = cart.acdc_items.map(item => ({
                name: item.name,
                description: "Item description",
                sku: item.id,
                product_code: item.id,
                commodity_code: "86101700",
                unit_of_measure: "UNIT",
                quantity: item.qty,
                category: "PHYSICAL_GOODS",
                unit_amount: {
                    currency_code: "USD",
                    value: item.price
                },
                unit_tax_amount: {
                    currency_code: "USD",
                    value: "0.00"
                },
                unit_discount_amount: { 
                    currency_code: "USD", 
                    value: "0.00" 
                }
            }));

            const orderPayload = {
                intent: "CAPTURE",
                purchase_units: [
                    {
                        reference_id: "ORDER-" + Date.now(),
                        invoice_id: "INV-" + Date.now(),
                        amount: {
                            currency_code: "USD",
                            value: cart.grand_total,
                            breakdown: {
                                item_total: {
                                    currency_code: "USD",
                                    value: cart.subtotal,
                                },
                                shipping: {
                                    currency_code: "USD",
                                    value: cart.shipping
                                },
                                handling: {
                                    currency_code: "USD",
                                    value: cart.handling
                                },
                                tax_total: {
                                    currency_code: "USD",
                                    value: cart.tax
                                },
                                discount: {
                                    currency_code: "USD",
                                    value: "0.00"
                                },
                                duty: { 
                                    currency_code: "USD",
                                    value: "0.00"
                                }
                            },
                        },
                        items: items,
                        shipping: {
                            name: {
                                full_name: payer.firstname + ' ' + payer.lastname
                            },
                            address: {
                                address_line_1: billing.street,
                                admin_area_2: billing.city,
                                admin_area_1: billing.state,
                                postal_code: billing.zip,
                                country_code: billing.countrycode
                            }
                        },
                        shipping_detail: {
                            ship_from_address: {
                                postal_code: "90001",
                                country_code: "US"
                            },
                        },
                    },
                ],
                payment_source: {
                    card: {
                        attributes: {
                            verification: {
                                method: "SCA_ALWAYS"
                            }
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