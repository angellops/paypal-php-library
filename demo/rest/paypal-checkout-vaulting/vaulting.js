document.addEventListener("DOMContentLoaded", function () {
    async function createVaultSetupToken() {
        try {
            const payloadData = {
                payment_source: {
                    paypal: {
                        usage_type: "MERCHANT",
                        customer_type: "CONSUMER",
                        experience_context: {
                            brand_name: "AngellEYE",
                            locale: "en-US",
                            shipping_preference: "NO_SHIPPING",
                            return_url: window.location.href,
                            cancel_url: window.location.href,
                            contact_preference: "UPDATE_CONTACT_INFO"
                        }
                    }
                }
            };

            const response = await fetch('../../core/paypal-api.php?action=ae_create_vault_setup_token', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payloadData)
            })

            const setupTokenData = await response.json();

            if (setupTokenData.debug_id) {
                fetch('../../core/paypal-api.php?action=ae_save_debug_ids', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'createVaultSetupToken', debug_id: setupTokenData.debug_id })
                });
            }

            if (!setupTokenData.setup_token) {
                showPaypalMessage("Unable to create Vault Setup Token.", "error");
                return;
            }

            return { vaultSetupToken: setupTokenData?.setup_token };
        } catch (error) {
            showPaypalMessage(error.message, "error");
            throw error;
        }
    }

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

    async function createPaymentToken(data) {
        try {
            const response = await fetch('../../core/paypal-api.php?action=ae_get_vault_setup_token', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: data.vaultSetupToken }),
            });
            const getSetupTokenData = await response.json();

            if (getSetupTokenData.debug_id) {
                fetch('../../core/paypal-api.php?action=ae_save_debug_ids', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'getSetupToken', debug_id: getSetupTokenData.debug_id })
                });
            }

            if (getSetupTokenData.status === "APPROVED") {
                const payloadData = {
                    payment_source: {
                        token: {
                            id: getSetupTokenData.setup_token,
                            type: "SETUP_TOKEN"
                        }
                    }
                };

                const response = await fetch('../../core/paypal-api.php?action=ae_create_vault_payment_token', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payloadData)
                })

                const vaultTokenData = await response.json();

                if (vaultTokenData.debug_id) {
                    fetch('../../core/paypal-api.php?action=ae_save_debug_ids', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'createPaymentToken', debug_id: vaultTokenData.debug_id })
                    });
                }

                if (!vaultTokenData.vault_token) {
                    showPaypalMessage("Unable to create Vault Payment Token.", "error");
                    return;
                }
                window.location.href = `createOrder.php?setupToken=${getSetupTokenData.setup_token}&customerID=${getSetupTokenData.customer_id}&paymentToken=${vaultTokenData.vault_token}`;
            } else {
                throw new Error("Payment capture failed.");
            }

        } catch (error) {
            showPaypalMessage(error.message, "error");
            throw error;
        }
    }

    const paymentSessionOptions = {
        async onApprove(data) {
            try {
                const orderData = await createPaymentToken({
                    vaultSetupToken: data.vaultSetupToken,
                });
            } catch (error) {
                showPaypalMessage(error.message, "error");
                throw error;
            }
        },
        onCancel() {
            location.reload();
        },
        onError(error) {
            showPaypalMessage(error.message, "error");
        },
    };

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
                paymentFlow: "VAULT_WITHOUT_PAYMENT",
            });

            if (paymentMethods.isEligible("paypal")) {
                setUpPayPalButton(sdkInstance);
                hidePaypalMessage();
            }
        } catch (error) {
            showPaypalMessage(`Initialization error: ${error.message}`, "error");
            throw error;
        }
    }

    // Set up standard PayPal button
    async function setUpPayPalButton(sdkInstance) {
        const paypalPaymentSession = sdkInstance.createPayPalSavePaymentSession(
            paymentSessionOptions,
        );

        const paypalButton = document.querySelector("paypal-button");
        paypalButton.removeAttribute("hidden");

        paypalButton.addEventListener("click", async () => {
            try {
                await paypalPaymentSession.start(
                    { presentationMode: "auto" },
                    createVaultSetupToken(),
                );
            } catch (error) {
                showPaypalMessage(`PayPal payment start error: ${error}`, "error")
            }
        });
    }

    init();
});