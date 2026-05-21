document.addEventListener("DOMContentLoaded", function () {
    const venmoButtonContainer = document.getElementById("venmo-container");
    if ( venmoButtonContainer !== undefined && venmoButtonContainer !== null ) {
        const checkoutData = JSON.parse(venmoButtonContainer.dataset.checkout);
        let selectedPaymentSource = null;
        
        async function createOrder(paymentSource) {
            try {
                const items = checkoutData.venmo_items.map(item => ({
                    name: item.name,
                    sku: item.id, 
                    unit_amount: {
                        currency_code: "USD",
                        value: item.price
                    },
                    quantity: item.qty,
                }));
    
                const orderPayload = {
                    intent: "CAPTURE",
                    purchase_units: [
                        {
                            items: items,
                            amount: {
                                currency_code: "USD",
                                value: checkoutData.grand_total,
                                breakdown: {
                                    item_total: {
                                        currency_code: "USD",
                                        value: checkoutData.subtotal,
                                    }
                                },
                            },
                        },
                    ],
                    payment_source: {
                        [paymentSource]: {
                            experience_context: {
                                brand_name: "AngellEYE",
                                shipping_preference: "GET_FROM_FILE",
                                return_url: window.location.href,
                                cancel_url: window.location.href,
                                contact_preference: "UPDATE_CONTACT_INFO"
                            }
                        }
                    }
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
                    showPaypalMessage("Unable to create PayPal order.", "error");
                    return;
                }
    
                return { orderId: data?.order_id };
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
    
        async function captureOrder(data) {
            try {
                const response = await fetch('../../core/paypal-api.php?action=ae_capture_order', {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: data.orderId }),
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
                    window.location.href = `getOrder.php?payment_source=${data.paymentSource}&order_id=${data.orderId}`;
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
                    const orderData = await captureOrder({
                        orderId: data.orderId,
                        paymentSource: selectedPaymentSource
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
                showPaypalMessage('Setting up PayPal & Venmo Button, please wait...', 'info');

                const res = await fetch('../../core/paypal-api.php?action=ae_client_token');
                const { token } = await res.json();
    
                const sdkInstance = await window.paypal.createInstance({
                    clientToken: token,
                    components: ["paypal-payments", "venmo-payments"],
                });
    
                const paymentMethods = await sdkInstance.findEligibleMethods({
                    currencyCode: "USD",
                    paymentFlow: "VAULT_WITH_PAYMENT",
                });
    
                const isPayPalEligible = paymentMethods.isEligible("paypal");
                if (isPayPalEligible) {
                    setupPayPalButton(sdkInstance);
                }
    
                const isVenmoEligible = paymentMethods.isEligible("venmo");
                if (isVenmoEligible) {
                    setupVenmoButton(sdkInstance);
                }
                
                if (isPayPalEligible || isVenmoEligible) {
                    hidePaypalMessage();
                }
            } catch (error) {
                showPaypalMessage(error.message, "error");
                throw error;
            }
        }
    
        async function setupPayPalButton(sdkInstance) {
            const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(
                paymentSessionOptions,
            );
    
            // Get reference to the Venmo button element
            const paypalButton = document.querySelector("#paypal-button");
            
            // Show the button since Venmo is eligible
            paypalButton.removeAttribute("hidden");
    
            paypalButton.addEventListener("click", async () => {
                try {
                    selectedPaymentSource = "paypal";
    
                    // Start the payment session
                    await paypalPaymentSession.start({ 
                            presentationMode: "auto" 
                        },
                        createOrder('paypal')
                    );
                } catch (error) {
                    showPaypalMessage(`Error: ${error.message}`, 'error');
                    throw error;
                }
            });
        }
    
        async function setupVenmoButton(sdkInstance) {
            const venmoPaymentSession = sdkInstance.createVenmoOneTimePaymentSession(
                paymentSessionOptions,
            );
    
            // Get reference to the Venmo button element
            const venmoButton = document.querySelector("#venmo-button");
            
            // Show the button since Venmo is eligible
            venmoButton.removeAttribute("hidden");
    
            venmoButton.addEventListener("click", async () => {
                try {
                    selectedPaymentSource = "venmo";
    
                    await venmoPaymentSession.start({ 
                            presentationMode: "auto" 
                        },
                        createOrder('venmo')
                    );
                } catch (error) {
                    showPaypalMessage(`Error: ${error.message}`, 'error');
                    throw error;
                }
            });
        }
        init();
    }
});