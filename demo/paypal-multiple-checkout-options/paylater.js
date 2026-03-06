document.addEventListener('DOMContentLoaded', function () {
    // Initialize PayPal Pay Later Messages on load
    initPayPalMessages();

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
                // Initialize PayLater Messaging
                const messagesInstance = sdkInstance.createPayPalMessages();

                const payLaterPaymentMethodDetails = paymentMethods.getDetails("paylater");
                const { productCode, countryCode } = payLaterPaymentMethodDetails;
                const payLaterButton = document.querySelector("paypal-pay-later-button");

                // Configure button with Pay Later specific details
                payLaterButton.productCode = productCode;
                payLaterButton.countryCode = countryCode;
                payLaterButton.removeAttribute("hidden");
            }
        } catch (error) {
            console.error("PayPal Message Init Error:", error);
        }
    }
});