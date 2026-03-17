document.addEventListener('DOMContentLoaded', function () {
    // --- DOM ELEMENT SELECTORS ---
    const quantityControls = document.querySelectorAll('.quantity-controls');
    if ( quantityControls.length > 0 ) {
        // Cart-specific total elements
        const isCartPage = document.querySelector('.cart-page');
        const cartSubtotalEl = document.querySelector('.cart-subtotal-value');
        const cartShippingEl = document.querySelector('.cart-shipping');
        const cartHandlingEl = document.querySelector('.cart-handling');
        const cartTaxEl = document.querySelector('.cart-tax');
        const cartTotalEl = document.querySelector('.cart-total-value');
    
        /**
         * INITIALIZE QUANTITY STEPPERS
         * Iterates through every quantity selector (Plus/Minus buttons)
         */
        quantityControls.forEach(stepper => {
            const valueEl = stepper.querySelector('.qty-value');
            const inputEl = stepper.querySelector('input[type="hidden"]');
            const plusBtn = stepper.querySelector('.qty-btn.plus');
            const minusBtn = stepper.querySelector('.qty-btn.minus');
    
            if (!valueEl || !inputEl) return;
    
            let value = parseInt(valueEl.textContent, 10) || 1;
    
            // Cart-only elements
            const row = isCartPage ? stepper.closest('.cart-item') : null;
            const price = row ? parseFloat(row.dataset.price) : null;
            const lineTotalEl = row ? row.querySelector('.line-total span') : null;
    
            // Extract product ID from hidden input name: qty[123_abc]
            const match = inputEl.name.match(/\[(.*?)\]/);
            const itemId = match ? match[1] : null;
    
            function updateUI() {
                // Always update quantity
                valueEl.textContent = value;
                inputEl.value = value;
    
                // Cart-only pricing logic
                if (isCartPage && row && lineTotalEl) {
                    lineTotalEl.textContent = (price * value).toFixed(2);
                    recalculateCartTotal();
    
                    // Update Quantity in Session
                    if (itemId) {
                        syncQtyToSession(itemId, value);
                    }
                }
            }
    
            plusBtn?.addEventListener('click', () => {
                value++;
                updateUI();
            });
    
            minusBtn?.addEventListener('click', () => {
                if (value > 1) {
                    value--;
                    updateUI();
                }
            });
        });
    
        /**
         * Recalculates the entire cart total based on current quantities in the DOM.
         * Triggers whenever a quantity is changed.
         */
        function recalculateCartTotal() {
            if (!isCartPage || !cartSubtotalEl || !cartShippingEl || !cartHandlingEl || !cartTaxEl || !cartTotalEl) return;
    
            let subtotal = 0;
    
            // Sum up line totals for all items
            document.querySelectorAll('.cart-item').forEach(row => {
                const price = parseFloat(row.dataset.price);
                const qty = parseInt(row.querySelector('.qty-value').textContent, 10);
                subtotal += price * qty;
            });
    
            // Parse fixed costs (Shipping, Tax, etc.), removing currency symbols
            const shipping = parseFloat(cartShippingEl.textContent.replace(/[^0-9.-]+/g, "")) || 0;
            const handling = parseFloat(cartHandlingEl.textContent.replace(/[^0-9.-]+/g, "")) || 0;
            const tax = parseFloat(cartTaxEl.textContent.replace(/[^0-9.-]+/g, "")) || 0;
    
            // Calculate Final Total
            const finalTotal = subtotal + shipping + handling + tax;
    
            // Update the UI with formatted decimals
            cartSubtotalEl.textContent = subtotal.toFixed(2);
            cartTotalEl.textContent = finalTotal.toFixed(2);
    
            if (window.updatePayLaterAmount) {
                window.updatePayLaterAmount(finalTotal);
            }
        }
    
        /**
         * Sends the updated quantity to the server via AJAX.
         * Syncs the PHP $_SESSION with the user's UI changes.
         */
        function syncQtyToSession(itemId, qty) {
            fetch('cart-update-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: itemId, qty: qty })
            });
        }
    }

    // CreateOrder and PayLater
    let checkoutData = null;
    const payLaterBtnContainer = document.getElementById("paylater-button-container");
    if ( payLaterBtnContainer !== undefined && payLaterBtnContainer !== null ) {
        checkoutData = JSON.parse(payLaterBtnContainer.dataset.checkout);
    }

    async function createOrder() {
        try {
            const checkoutCartData = checkoutData.cart;
            const checkoutBillingData = checkoutData.billing;
            const checkoutPayerData = checkoutData.payer;

            const items = Object.entries(checkoutCartData.paylater_items).map(([sku, item]) => ({
                name: item.name,
                sku: sku,
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
                            value: Number(checkoutCartData.grand_total).toFixed(2),
                            breakdown: {
                                item_total: {
                                    currency_code: "USD",
                                    value: Number(checkoutCartData.subtotal).toFixed(2),
                                },
                                shipping: {
                                    currency_code: "USD",
                                    value: Number(checkoutCartData.shipping).toFixed(2),
                                },
                                handling: {
                                    currency_code: "USD",
                                    value: Number(checkoutCartData.handling).toFixed(2),
                                },
                                tax_total: {
                                    currency_code: "USD",
                                    value: Number(checkoutCartData.tax).toFixed(2),
                                }
                            },
                        },
                        items: items,
                        shipping: {
                            name: {
                                full_name: checkoutPayerData.firstname + ' ' + checkoutPayerData.lastname
                            },
                            address: {
                                address_line_1: checkoutBillingData.street,
                                admin_area_2: checkoutBillingData.city,
                                admin_area_1: checkoutBillingData.state,
                                postal_code: checkoutBillingData.zip,
                                country_code: checkoutBillingData.countrycode
                            },
                            email_address: checkoutPayerData.email
                        },
                    },
                ],
                payment_source: {
                    paypal: {
                        experience_context: {
                            brand_name: "AngellEYE",
                            shipping_preference: "SET_PROVIDED_ADDRESS"
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

    // Initialize paymentSessionOptions for later use
    const paymentSessionOptions = {
        async onApprove(data) {
            window.location.href = `getOrder.php?order_id=${data.orderId}`;
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
    async function initPayPalMessages() {
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

            if (paymentMethods.isEligible("paylater")) {
                setUpPayLaterButton(sdkInstance, paymentMethods);
            }

            // This function is exposed globally to be called by recalculateCartTotal()
            window.updatePayLaterAmount = async function(newAmount) {
                const messageElement = document.querySelector('paypal-message');
                if (messageElement) {
                    messageElement.setAttribute('amount', newAmount.toFixed(2));
                }
            };
        } catch (error) {
            showPaypalError(`PayPal Message Init Error: ${error}`)
        }
    }

    // Set up standard PayPal button
    async function setUpPayLaterButton(sdkInstance, paymentMethods) {
        const payLaterPaymentMethodDetails = paymentMethods.getDetails("paylater");
        const { productCode, countryCode } = payLaterPaymentMethodDetails;

        // Initialize PayLater Messaging
        const messagesInstance = sdkInstance.createPayPalMessages();
        
        const paypalPaymentSession = sdkInstance.createPayLaterOneTimePaymentSession(
            paymentSessionOptions,
        );

        const payLaterButton = document.querySelector("paypal-pay-later-button");
        if ( !payLaterButton ) return;

        payLaterButton.productCode = productCode;
        payLaterButton.countryCode = countryCode;
        payLaterButton.removeAttribute("hidden");
        payLaterButton.addEventListener("click", async () => {
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

    // Initialize PayPal Pay Later Messages on load
    initPayPalMessages();
});