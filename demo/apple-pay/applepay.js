document.addEventListener("DOMContentLoaded", function () {
    const applepayButtonContainer = document.getElementById("applepay-button-container");
    if ( !applepayButtonContainer ) return;

    const purchaseAmount = applepayButtonContainer.dataset.amount;

    async function createOrder(purchaseAmount) {
        const orderPayload = {
            intent: "CAPTURE",
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
        };

        const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_create_order', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(orderPayload)
        })

        const data = await response.json();
        return data.id;
    }

    async function init() {
        try {
            if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
                applepayButtonContainer.innerHTML = "<p>Apple Pay is not available on this device or browser. Please use Safari on macOS or iOS.</p>";
                return;
            }

            const res = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_client_token');
            const { token } = await res.json();
            
            const sdkInstance = await window.paypal.createInstance({
                clientToken: token,
                components: ["applepay-payments"],
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
                                console.error(`Merchant validation error: ${err.message}`);
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

                            console.log("Capturing payment...");
                            const response = await fetch('../../src/angelleye/PayPal/api/paypal-api.php?action=ae_capture_order', {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ id: createdOrder }),
                            });

                            const captureResult = await response.json();

                            if (captureResult.status === "COMPLETED") {
                                window.location.href = `getOrder.php?order_id=${createdOrder}`;
                            }

                            session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});
                        } catch (err) {
                            session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                        }
                    };

                    session.oncancel = () => {
                        console.log("Apple Pay payment cancelled by user");
                    };

                    session.begin();
                } catch (error) {
                    console.error(`Error starting Apple Pay: ${error.message}`);
                }
            }

            applePayBtn.addEventListener("click", onApplePayButtonClick);
            applepayButtonContainer.appendChild(applePayBtn);
        } catch (error) {
            console.error(`Initialization error: ${error.message}`);
        }
    }
    init();
});