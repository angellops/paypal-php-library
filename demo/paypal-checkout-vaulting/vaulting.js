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
                            cancel_url: window.location.href
                        }
                    }
                }
            };

            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_vault_setup_token&vaulting=true', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payloadData)
            })

            const setupTokenData = await response.json();
            if (!setupTokenData.setup_token) {
                showPaypalError("Unable to create Vault Setup Token.");
                return;
            }

            return { vaultSetupToken: setupTokenData?.setup_token };
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

    async function createPaymentToken(data) {
        try {
            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_get_vault_setup_token&vaulting=true', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: data.vaultSetupToken }),
            });
            const getSetupTokenData = await response.json();

            if (getSetupTokenData.status === "APPROVED") {
                const payloadData = {
                    payment_source: {
                        token: {
                            id: getSetupTokenData.setup_token,
                            type: "SETUP_TOKEN"
                        }
                    }
                };

                const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_vault_payment_token&vaulting=true', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payloadData)
                })

                const vaultTokenData = await response.json();
                if (!vaultTokenData.vault_token) {
                    showPaypalError("Unable to create Vault Payment Token.");
                    return;
                }
                window.location.href = `createOrder.php?setupToken=${getSetupTokenData.setup_token}&customerID=${getSetupTokenData.customer_id}&paymentToken=${vaultTokenData.vault_token}`;
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
                const orderData = await createPaymentToken({
                    vaultSetupToken: data.vaultSetupToken,
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
                paymentFlow: "VAULT_WITHOUT_PAYMENT",
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
                showPaypalError(`PayPal payment start error: ${error}`)
            }
        });
    }

    init();
});