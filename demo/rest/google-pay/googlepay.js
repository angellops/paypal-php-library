document.addEventListener("DOMContentLoaded", function () {
    const gpayContainerMain = document.getElementById("gpay-container-main");
    if ( gpayContainerMain !== undefined && gpayContainerMain !== null ) {
        const checkoutData = JSON.parse(gpayContainerMain.dataset.checkout);
        const purchaseAmount = checkoutData.grand_total;
        const gpayButtonContainer = gpayContainerMain.querySelector("#googlepay-button-container");

        async function createOrder(purchaseAmount) {
            try {
                const orderPayload = {
                    intent: "CAPTURE",
                    payer: {
                        email_address: checkoutData.buyer_email,
                    },
                    purchase_units: [
                        {
                            amount: {
                                currency_code: "USD",
                                value: Number(purchaseAmount).toFixed(2),
                                breakdown: {
                                    item_total: {
                                        currency_code: "USD",
                                        value: Number(purchaseAmount).toFixed(2),
                                    },
                                },
                            },
                        },
                    ],
                    payment_source: {
                        google_pay: {
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

                if (data.debug_id) {
                    fetch('../../core/paypal-api.php?action=ae_save_debug_ids', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'createOrder', debug_id: data.debug_id })
                    });
                }

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

        function getGoogleTransactionInfo(purchaseAmount, countryCode) {
            const totalAmount = parseFloat(purchaseAmount);
            const subtotal = (totalAmount * 0.9).toFixed(2);
            const tax = (totalAmount * 0.1).toFixed(2);

            return {
                displayItems: [
                    { label: "Subtotal", type: "SUBTOTAL", price: subtotal },
                    { label: "Tax", type: "TAX", price: tax },
                ],
                countryCode: countryCode,
                currencyCode: "USD",
                totalPriceStatus: "FINAL",
                totalPrice: purchaseAmount,
                totalPriceLabel: "Total",
            };
        }

        async function getGooglePaymentDataRequest(purchaseAmount, googlePayConfig) {
            const { allowedPaymentMethods, merchantInfo, apiVersion, apiVersionMinor, countryCode, } = googlePayConfig;

            const baseRequest = { apiVersion, apiVersionMinor };
            const paymentDataRequest = Object.assign({}, baseRequest);

            paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
            paymentDataRequest.transactionInfo = getGoogleTransactionInfo(purchaseAmount, countryCode);
            paymentDataRequest.merchantInfo = {
                merchantName: checkoutData.brand_name,
                merchantId: checkoutData.merchant_id,
            };
            paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

            return paymentDataRequest;
        }

        async function onGooglePayButtonClicked(purchaseAmount, paymentsClient, googlePayConfig) {
            try {
                const paymentDataRequest = await getGooglePaymentDataRequest(purchaseAmount, googlePayConfig);
                paymentsClient.loadPaymentData(paymentDataRequest);
            } catch (error) {
                showPaypalMessage((error.message || "Google Pay payment failed."), "error");
                console.error(error);
            }
        }

        async function onPaymentAuthorized(purchaseAmount, paymentData, googlePaySession) {
            try {
                const id = await createOrder(purchaseAmount);

                const { status } = await googlePaySession.confirmOrder({
                    orderId: id,
                    paymentMethodData: paymentData.paymentMethodData,
                });

                if (status === "PAYER_ACTION_REQUIRED") {
                    showPaypalMessage("Additional authentication is required to complete the payment.", "error");
                    return { transactionState: "ERROR" };
                } 

                const response = await fetch('../../core/paypal-api.php?action=ae_capture_order', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id }),
                });

                const captureResult = await response.json();

                if (captureResult.debug_id) {
                    fetch('../../core/paypal-api.php?action=ae_save_debug_ids', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'captureOrder', debug_id: captureResult.debug_id })
                    });
                }

                if (captureResult.status === "COMPLETED") {
                    window.location.href = `getOrder.php?order_id=${id}`;
                } else {
                    throw new Error("Payment capture failed.");
                }

                return { transactionState: "SUCCESS" };
            } catch (err) {
                showPaypalMessage((err.message || "Payment authorization failed."), "error");
                console.error(err);
                return {
                    transactionState: "ERROR",
                    error: { message: err.message },
                };
            }
        }

        async function init() {
            try {
                showPaypalMessage('Setting up PayPal Button, please wait...', 'info');

                const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
                const { token } = await res.json();
                
                const sdkInstance = await window.paypal.createInstance({
                    clientToken: token,
                    components: ["googlepay-payments"],
                    pageType: 'checkout',
                });

                const googlePaySession = sdkInstance.createGooglePayOneTimePaymentSession();

                const paymentsClient = new google.payments.api.PaymentsClient({
                    environment: checkoutData.environment_mode,
                    paymentDataCallbacks: {
                        onPaymentAuthorized: (paymentData) => onPaymentAuthorized(purchaseAmount, paymentData, googlePaySession),
                    },
                });

                const googlePayConfig = await googlePaySession.getGooglePayConfig();

                await paymentsClient.isReadyToPay({
                    allowedPaymentMethods: googlePayConfig.allowedPaymentMethods,
                    apiVersion: googlePayConfig.apiVersion,
                    apiVersionMinor: googlePayConfig.apiVersionMinor,
                });

                const button = paymentsClient.createButton({
                    onClick: () =>
                        onGooglePayButtonClicked(purchaseAmount, paymentsClient, googlePayConfig),
                    }
                );

                gpayButtonContainer.appendChild(button);
                hidePaypalMessage();
            } catch (error) {
                showPaypalMessage((error.statusMessage || "Failed to initialize Google Pay."), "error");
                console.error(error);
            }
        }

        init();
    }
});