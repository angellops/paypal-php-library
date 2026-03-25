document.addEventListener("DOMContentLoaded", function () {
    // Show PayPal message
    function showPaypalMessage(message, type = 'info') {
        const container = document.getElementById('paypalMessage');
        if (!container) return;

        // Reset classes
        container.className = 'paypal-message';

        // Add type class (info / error)
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

    async function init() {
        try {
            showPaypalMessage('Setting up PayPal Button, please wait...', 'info');

            const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
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
                hidePaypalMessage();
                
                // Remove Hidden attribute from PayPal button
                const paypalButton = document.getElementById("paypalbtn");
                paypalButton.removeAttribute("hidden");
            }
        } catch (error) {
            showPaypalMessage(`Initialization error: ${error.message}`, 'error');
            throw error;
        }
    }

    init();
});