document.addEventListener("DOMContentLoaded", function () {
    const gpayButtonContainer = document.getElementById("googlepay-button-container");
    if ( !gpayButtonContainer ) return;

    const purchaseAmount = gpayButtonContainer.dataset.amount;
    const buyerEmail = gpayButtonContainer.dataset.email;

    async function createOrder(purchaseAmount) {
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
                google_pay: {
                    experience_context: {
                        brand_name: "AngellEYE Payment Demo",
                        locale: "en-US",
                        landing_page: "LOGIN",
                        user_action: "PAY_NOW",
                    },
                },
            },
        };

        const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_order', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(orderPayload)
        })

        const data = await response.json();
        return data.id;
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
            merchantName: "AngellEYE Payment Demo",
            merchantId: "BCR2DN5TRC26ZQ3J",
        };
        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

        return paymentDataRequest;
    }

    async function onGooglePayButtonClicked(purchaseAmount, paymentsClient, googlePayConfig) {
        try {
            const paymentDataRequest = await getGooglePaymentDataRequest(purchaseAmount, googlePayConfig);
            paymentsClient.loadPaymentData(paymentDataRequest);
        } catch (error) {
            console.error(`Error processing Google Pay payment: ${error.message}`);
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
                console.warn("3DS Flow Required - additional authentication needed");
            } else {
                console.log("Capturing payment...");
                const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_capture_order', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id }),
                });

                const captureResult = await response.json();

                if (captureResult.status === "COMPLETED") {
                    window.location.href = `getOrder.php?order_id=${id}`;
                }
            }

            return { transactionState: "SUCCESS" };
        } catch (err) {
            console.error(`Payment authorization error: ${err.message}`);
            return {
                transactionState: "ERROR",
                error: { message: err.message },
            };
        }
    }

    async function init() {
        try {
            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();
            
            const sdkInstance = await window.paypal.createInstance({
                clientToken: token,
                components: ["googlepay-payments"],
                pageType: 'checkout',
            });

            const googlePaySession = sdkInstance.createGooglePayOneTimePaymentSession();

            const paymentsClient = new google.payments.api.PaymentsClient({
                environment: "TEST",
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
        } catch (error) {
            console.error(`Initialization error: ${error.message}`);
        }
    }

    init();
});