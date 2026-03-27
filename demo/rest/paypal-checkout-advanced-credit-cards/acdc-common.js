document.addEventListener("DOMContentLoaded", function () {
    const mainContainer = document.getElementById("acdc-container");
    if (!mainContainer) return;

    // Extract checkout data from the container
    const checkoutData = JSON.parse(mainContainer.dataset.checkout);
    const payButton = document.querySelector("#pay-button");

    // Show PayPal message
    function showPaypalMessage(message, type = 'info') {
        const container = document.getElementById('paypalMessage');
        if (!container) return;

        // Reset and set classes
        container.className = 'paypal-message';
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

    /**
     * Exact Payload Generator per Mode (with Level 2/3 and Unit Tax)
     */
    async function createOrder(mode) {
        try {
            const cart = checkoutData.cart || checkoutData;
            const billing = checkoutData.billing || {};
            const payer = checkoutData.payer || {};
            const itemsList = cart.acdc_items || checkoutData.acdc_items;

            const items = itemsList.map(item => ({
                name: item.name,
                description: item.desc,
                sku: item.id,
                product_code: item.id,
                commodity_code: "86101700",
                unit_of_measure: "UNIT",
                quantity: item.qty.toString(),
                category: item.category || "PHYSICAL_GOODS",
                unit_amount: {
                    currency_code: "USD",
                    value: Number(item.price).toFixed(2)
                },
                unit_tax_amount: {
                    currency_code: "USD",
                    value: Number(item.tax || 0).toFixed(2)
                },
                unit_discount_amount: { 
                    currency_code: "USD", 
                    value: "0.00" 
                }
            }));

            let purchaseUnit = {
                amount: {
                    currency_code: "USD",
                    value: Number(cart.grand_total || checkoutData.grand_total).toFixed(2),
                    breakdown: {
                        item_total: { currency_code: "USD", value: Number(cart.subtotal || checkoutData.subtotal).toFixed(2) },
                        shipping: { currency_code: "USD", value: Number(cart.shipping || checkoutData.shipping).toFixed(2) },
                        handling: { currency_code: "USD", value: Number(cart.handling || checkoutData.handling).toFixed(2) },
                        tax_total: { currency_code: "USD", value: Number(cart.tax || checkoutData.tax).toFixed(2) },
                        discount: { currency_code: "USD", value: "0.00" },
                        duty: { currency_code: "USD", value: "0.00" }
                    },
                },
                items: items
            };

            let paymentSource = null;

            if (mode === 'paypal') {
                paymentSource = {
                    paypal: {
                        experience_context: {
                            brand_name: "AngellEYE",
                            shipping_preference: "GET_FROM_FILE",
                            user_action: "PAY_NOW"
                        }
                    }
                };
            } else {
                purchaseUnit.reference_id = "ORDER-" + Date.now();
                purchaseUnit.invoice_id = "INV-" + Date.now();
                purchaseUnit.shipping = {
                    name: { full_name: (payer.firstname || '') + ' ' + (payer.lastname || '') },
                    address: {
                        address_line_1: billing.street,
                        admin_area_2: billing.city,
                        admin_area_1: billing.state,
                        postal_code: billing.zip,
                        country_code: billing.countrycode
                    }
                };
                purchaseUnit.shipping_detail = {
                    ship_from_address: { postal_code: "90001", country_code: "US" }
                };

                if (mode === 'guest') {
                    paymentSource = {
                        card: { attributes: { verification: { method: "SCA_ALWAYS" } } }
                    };
                }
            }

            // Path updated to ../../core/paypal-api.php
            const response = await fetch('../../core/paypal-api.php?action=ae_create_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    intent: "CAPTURE",
                    purchase_units: [purchaseUnit],
                    ...(paymentSource && { payment_source: paymentSource })
                })
            });

            const data = await response.json();
            if (!data.order_id) throw new Error(data.message || "Order creation failed.");
            return data.order_id;

        } catch (error) {
            showPaypalMessage(`Order Error: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * API Capture
     */
    async function captureOrder(orderId, mode) {
        try {
            // Path updated to ../../core/paypal-api.php
            const response = await fetch('../../core/paypal-api.php?action=ae_capture_order', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: orderId }),
            });

            const result = await response.json();

            if (result.status === "COMPLETED") {
                window.location.href = `getOrder.php?payment_mode=${mode}&order_id=${orderId}`;
            } else {
                throw new Error(result.message || "The payment could not be captured.");
            }
        } catch (error) {
            showPaypalMessage(`Capture Error: ${error.message}`, 'error');
            if (payButton) {
                payButton.removeAttribute('disabled');
                payButton.innerHTML = "Place Order";
            }
        }
    }

    /**
     * Component Initialization
     */
    async function init() {
        try {
            showPaypalMessage('Setting up required buttons, please wait...', 'info');

            // Path updated to ../../core/paypal-api.php
            const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();

            const sdkInstance = await window.paypal.createInstance({
                clientToken: token,
                components: ['paypal-payments', 'card-fields', 'paypal-guest-payments'],
                pageType: 'checkout'
            });

            const paymentMethods = await sdkInstance.findEligibleMethods({ currencyCode: "USD" });

            if( paymentMethods.isEligible("paypal") || paymentMethods.isEligible("advanced_cards") ) {
                hidePaypalMessage();
            }

            // 1. Setup Standard PayPal
            if (paymentMethods.isEligible("paypal")) {
                const paypalBtn = document.querySelector("#paypal-button");
                paypalBtn.removeAttribute("hidden");
                const session = sdkInstance.createPayPalOneTimePaymentSession({
                    onApprove: (data) => captureOrder(data.orderId, 'paypal'),
                    onCancel: () => showPaypalMessage("PayPal payment cancelled.", 'info'),
                    onError: (err) => showPaypalMessage(`PayPal Error: ${err.message}`, 'error')
                });
                paypalBtn.addEventListener("click", async () => {
                    await session.start({ presentationMode: "auto" }, createOrder('paypal').then(id => ({ orderId: id })));
                });
            }

            // 2. Setup Guest Checkout
            const guestBtn = document.querySelector("#paypal-basic-card-button");
            if (guestBtn) {
                guestBtn.removeAttribute("hidden");
                const guestSession = sdkInstance.createPayPalGuestOneTimePaymentSession({
                    onApprove: (data) => captureOrder(data.orderId, 'guest'),
                    onCancel: () => showPaypalMessage("Guest checkout cancelled.", 'info'),
                    onError: (err) => showPaypalMessage(`Guest Error: ${err.message}`, 'error')
                });
                guestBtn.addEventListener("click", async () => {
                    await guestSession.start({ presentationMode: "auto" }, createOrder('guest').then(id => ({ orderId: id })));
                });
            }

            // 3. Setup ACDC
            if (paymentMethods.isEligible("advanced_cards")) {
                setupACDC(sdkInstance);
            }
        } catch (error) {
            showPaypalMessage(`Initialization Failed: ${error.message}`, 'error');
        }
    }

    /**
     * ACDC Setup & Logic
     */
    async function setupACDC(sdkInstance) {
        const cardFields = sdkInstance.createCardFieldsOneTimePaymentSession();
        
        document.querySelector("#paypal-card-fields-number").appendChild(cardFields.createCardFieldsComponent({ type: "number", placeholder: "Card Number" }));
        document.querySelector("#paypal-card-fields-expiry").appendChild(cardFields.createCardFieldsComponent({ type: "expiry", placeholder: "MM/YY" }));
        document.querySelector("#paypal-card-fields-cvv").appendChild(cardFields.createCardFieldsComponent({ type: "cvv", placeholder: "CVV" }));

        payButton.addEventListener("click", async () => {
            payButton.setAttribute('disabled', 'true');
            payButton.innerHTML = "Processing Payment...";
            
            try {
                const orderId = await createOrder('acdc');
                const { data, state } = await cardFields.submit(orderId, {
                    cardholderName: `${checkoutData.payer.firstname} ${checkoutData.payer.lastname}`,
                    billingAddress: {
                        addressLine1: checkoutData.billing.street,
                        adminArea2: checkoutData.billing.city,
                        adminArea1: checkoutData.billing.state,
                        postalCode: checkoutData.billing.zip,
                        countryCode: checkoutData.billing.countrycode
                    },
                    contingencies: ["SCA_ALWAYS"]
                });

                switch (state) {
                    case "succeeded":
                        await captureOrder(data.orderId, 'acdc');
                        break;
                    case "canceled":
                        showPaypalMessage("Card payment was cancelled.", "info");
                        payButton.removeAttribute('disabled');
                        payButton.innerHTML = "Place Order";
                        break;
                    case "failed":
                        showPaypalMessage(`Card Submission Failed: ${data.message || "Please check your card details."}`, "error");
                        payButton.removeAttribute('disabled');
                        payButton.innerHTML = "Place Order";
                        break;
                    default:
                        showPaypalMessage(`Unexpected card state: ${state}`, "error");
                        payButton.removeAttribute('disabled');
                        payButton.innerHTML = "Place Order";
                }
            } catch (err) {
                showPaypalMessage(`Payment Flow Error: ${err.message}`, "error");
                payButton.removeAttribute('disabled');
                payButton.innerHTML = "Place Order";
            }
        });
    }

    init();
});