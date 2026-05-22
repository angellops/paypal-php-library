document.addEventListener("DOMContentLoaded", function () {
    const applepayContainerMain = document.getElementById("applepay-container-main");
    if ( applepayContainerMain !== undefined && applepayContainerMain !== null ) {
        const purchaseAmount = applepayContainerMain.dataset.amount;
        const buyerEmail = applepayContainerMain.dataset.email;
        const applepayButtonContainer = applepayContainerMain.querySelector("#applepay-button-container");
    
        async function createOrder(purchaseAmount) {
            try {
                const orderPayload = {
                    intent: "CAPTURE",
                    payer: {
                        email_address: buyerEmail,
                    },
                    purchase_units: [
                        {
                            amount: {
                                currency_code: "USD",
                                value: purchaseAmount,
                                breakdown: {
                                    item_total: {
                                        currency_code: "USD",
                                        value: purchaseAmount,
                                    },
                                },
                            },
                        },
                    ],
                    payment_source: {
                        apple_pay: {
                            experience_context: {
                                return_url: window.location.href,
                                cancel_url: window.location.href,
                                contact_preference: "UPDATE_CONTACT_INFO"
                            },
                        },
                    },
                };
    
                const response = await fetch('../../core/paypal-api.php?action=ae_create_order', {
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
    
        async function init() {
            try {
                showPaypalMessage('Setting up PayPal Button, please wait...', 'info');

                if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
                    showPaypalMessage("Apple Pay is not available on this device or browser. Please use Safari on macOS or iOS.", "error");
                    return;
                }
    
                const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
                const { token } = await res.json();
                
                const sdkInstance = await window.paypal.createInstance({
                    clientToken: token,
                    components: ["applepay-payments"],
                    pageType: 'checkout',
                });
    
                const applePaySession = sdkInstance.createApplePayOneTimePaymentSession();
    
                const { merchantCapabilities, supportedNetworks } = await applePaySession.config();
    
                // Create Apple Pay button
                const ApplePayButton = window.customElements.get("apple-pay-button");
                const applePayBtn = new ApplePayButton();
    
                if (typeof applePayBtn.buttonStyle !== "undefined") {
                    applePayBtn.buttonStyle = "black";
                    applePayBtn.type = "buy";
                    applePayBtn.locale = "en";
                } else {
                    applePayBtn.textContent = "Apple Pay";
                    applePayBtn.style.backgroundColor = "black";
                    applePayBtn.style.color = "white";
                    applePayBtn.style.borderRadius = "4px";
                    applePayBtn.style.padding = "10px 20px";
                }
    
                async function onApplePayButtonClick() {
                    try {
                        const paymentRequest = {
                            countryCode: "US",
                            currencyCode: "USD",
                            merchantCapabilities,
                            supportedNetworks,
                            requiredBillingContactFields: [
                                "name", "phone", "email", "postalAddress",
                            ],
                            requiredShippingContactFields: [],
                            total: {
                                label: "AngellEYE Payment Demo",
                                amount: purchaseAmount,
                                type: "final",
                            },
                        };
    
                        let session = new ApplePaySession(4, paymentRequest);
    
                        session.onvalidatemerchant = (event) => {
                            applePaySession
                                .validateMerchant({
                                    validationUrl: event.validationURL,
                                })
                                .then((payload) => {
                                    session.completeMerchantValidation(payload.merchantSession);
                                })
                                .catch((err) => {
                                    showPaypalMessage(err.message || "Apple Pay merchant validation failed.", "error");
                                    console.error(err);
                                    session.abort();
                                });
                        };
    
                        session.onpaymentmethodselected = () => {
                            session.completePaymentMethodSelection({
                                newTotal: paymentRequest.total,
                            });
                        };
    
                        session.onpaymentauthorized = async (event) => {
                            try {
                                const createdOrder = await createOrder(purchaseAmount);
    
                                await applePaySession.confirmOrder({
                                    orderId: createdOrder,
                                    token: event.payment.token,
                                    billingContact: event.payment.billingContact,
                                    shippingContact: event.payment.shippingContact,
                                });
    
                                const response = await fetch('../../core/paypal-api.php?action=ae_capture_order', {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ id: createdOrder }),
                                });
    
                                const captureResult = await response.json();
    
                                if (captureResult.status === "COMPLETED") {
                                    window.location.href = `getOrder.php?order_id=${createdOrder}`;
                                    session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});
                                } else {
                                    throw new Error("Payment capture failed.");
                                }
                            } catch (err) {
                                showPaypalMessage((err.message || "Apple Pay payment failed."), "error");
                                console.error(err);
                                session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                            }
                        };
    
                        session.oncancel = () => {
                            showPaypalMessage("Apple Pay payment cancelled by user", "info");
                        };
    
                        session.begin();
                    } catch (error) {
                        showPaypalMessage((error.message || "Error starting Apple Pay Payment."), "error");
                    }
                }
    
                applePayBtn.addEventListener("click", onApplePayButtonClick);
                applepayButtonContainer.appendChild(applePayBtn);
                hidePaypalMessage();
            } catch (error) {
                showPaypalMessage((error.statusMessage || "Failed to initialize Apple Pay."), "error");
            }
        }
        init();
    }
});