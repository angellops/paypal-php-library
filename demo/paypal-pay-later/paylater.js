document.addEventListener('DOMContentLoaded', function () {
    // Initialize PayPal Pay Later Messages on load
    initPayPalMessages();

    // --- DOM ELEMENT SELECTORS ---
    const quantityControls = document.querySelectorAll('.quantity-controls');
    if (!quantityControls.length) return;

    // Cart-specific total elements
    const isCartPage = document.querySelector('.cart-page');
    const cartSubtotalEl = document.querySelector('.cart-subtotal-value');
    const cartShippingEl = document.querySelector('.cart-shipping');
    const cartHandlingEl = document.querySelector('.cart-handling');
    const cartTaxEl = document.querySelector('.cart-tax');
    const cartTotalEl = document.querySelector('.cart-total-value');

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
     * PAYPAL V6 SDK INITIALIZATION
     * Fetches a client token and sets up the financing messages.
     */
    async function initPayPalMessages() {
        try {
            const res = await fetch('../../src/angelleye/PayPal/api/token.php');
            const { token } = await res.json();

            const sdkInstance = await paypal.createInstance({
                clientToken: token,
                components: ["paypal-payments"],
            });

            const messagesInstance = sdkInstance.createPayPalMessages();
            if (document.body.classList.contains('checkout-page')) {
                const paymentMethods = await sdkInstance.findEligibleMethods({
                    currencyCode: "USD",
                });
    
                if (paymentMethods.isEligible("paylater")) {
                    const payLaterPaymentMethodDetails = paymentMethods.getDetails("paylater");
                    const { productCode, countryCode } = payLaterPaymentMethodDetails;
                    const payLaterButton = document.querySelector("paypal-pay-later-button");
    
                    // Configure button with Pay Later specific details
                    payLaterButton.productCode = productCode;
                    payLaterButton.countryCode = countryCode;
                    payLaterButton.removeAttribute("hidden");
                }
            }

            // This function is exposed globally to be called by recalculateCartTotal()
            window.updatePayLaterAmount = async function(newAmount) {
                const messageElement = document.querySelector('paypal-message');
                if (messageElement) {
                    messageElement.setAttribute('amount', newAmount.toFixed(2));
                }
            };
        } catch (error) {
            console.error("PayPal Message Init Error:", error);
        }
    }
});