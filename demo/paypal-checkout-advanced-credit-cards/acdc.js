document.addEventListener("DOMContentLoaded", function () {
    const acdcButtonContainer = document.getElementById("acdc-container");
    if ( !acdcButtonContainer ) return;

    const payButton = document.querySelector("#pay-button");

    const checkoutData = JSON.parse(acdcButtonContainer.dataset.checkout);

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
            };

            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(orderPayload)
            })

            const data = await response.json();
            console.log("Order creation response:", data);
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
        acdcButtonContainer.innerHTML = message;
    }

    function showActions() {
        payButton.setAttribute('disabled', 'true');
        payButton.innerHTML = "Processing Payment...";
    }

    function hideActions() {
        payButton.removeAttribute('disabled');
        payButton.innerHTML = "Place Order";
    }

    async function captureOrder(orderId) {
        try {
            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_capture_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: orderId }),
            });

            const captureResult = await response.json();

            if (captureResult.status === "COMPLETED") {
                hideActions();
                window.location.href = `getOrder.php?payment_mode=acdc&order_id=${orderId}`;
            } else {
                hideActions();
                throw new Error("Payment capture failed.");
            }

        } catch (error) {
            hideActions();
            showPaypalError(error.message);
            throw error;
        }
    }

    async function init() {
        // Hide Pay Button
        payButton.style.display = "none";

        try {
            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();

            const sdkInstance = await window.paypal.createInstance({
                clientToken: token,
                components: ["card-fields"],
            });

            const paymentMethods = await sdkInstance.findEligibleMethods();
            const isCardFieldsEligible = paymentMethods.isEligible("advanced_cards");
            if (isCardFieldsEligible) {
                setupCardFields(sdkInstance);
            }
        } catch (error) {
            showPaypalError(`Initialization error: ${error.message}`);
            throw error;
        }
    }

    async function setupCardFields(sdkInstance) {
        const cardFieldsInstance = sdkInstance.createCardFieldsOneTimePaymentSession();

        const numberField = cardFieldsInstance.createCardFieldsComponent({
            type: "number",
            placeholder: "Card Number",
        });

        const expiryField = cardFieldsInstance.createCardFieldsComponent({
            type: "expiry",
            placeholder: "MM/YY",
        });

        const cvvField = cardFieldsInstance.createCardFieldsComponent({
            type: "cvv",
            placeholder: "CVV",
        });

        document.querySelector("#paypal-card-fields-number").appendChild(numberField);
        document.querySelector("#paypal-card-fields-cvv").appendChild(cvvField);
        document.querySelector("#paypal-card-fields-expiry").appendChild(expiryField);
        
        payButton.style.display = "inline-block";
        payButton.addEventListener("click", () => onPayClick(cardFieldsInstance));
    }

    async function onPayClick(cardFieldsInstance) {
        showActions();

        try {
            const orderId = await createOrder();

            const { data, state } = await cardFieldsInstance.submit(orderId, {
                cardholderName: checkoutData.payer.firstname + " " + checkoutData.payer.lastname,
                billingAddress: {
                    addressLine1: checkoutData.billing.street,
                    adminArea2: checkoutData.billing.city,
                    adminArea1: checkoutData.billing.state,
                    postalCode: checkoutData.billing.zip,
                    countryCode: checkoutData.billing.countrycode,
                },
                contingencies: ["SCA_ALWAYS"]
            });

            switch (state) {
                case "succeeded": {
                    const orderData = await captureOrder(data.orderId);
                    break;
                }
                case "canceled": {
                    hideActions();
                    location.reload();
                    break;
                }
                case "failed": {
                    hideActions();
                    showPaypalError(`Card submission failed: ${data.message || data}`);
                    break;
                }
                default: {
                    hideActions();
                    showPaypalError(`Unhandled submit state: ${state}`);
                }
            }
        } catch (err) {
            hideActions();
            console.error("Payment flow error", err);
        }
    }

    init();
});