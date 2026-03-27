document.addEventListener("DOMContentLoaded", async function () {
    const container = document.getElementById("multicheckout-container");
    if (!container) return;

    const checkoutData = JSON.parse(container.dataset.checkout);

    const payButton = document.getElementById("pay-button");
    const paypalBtn = document.getElementById("paypalbtn");
    const guestBtn = document.getElementById("paypal-basic-card-button");
    const venmoBtn = document.getElementById("venmo-button");
    const payLaterBtn = document.getElementById("paylater-button");

    // Show PayPal message
    function showPaypalMessage(message, type = "info") {
        const el = document.getElementById("paypalMessage");
        if (!el) return;

        el.className = "paypal-message";
        el.classList.add(type);
        el.style.display = "block";
        el.innerHTML = message;
    }

    // Hide PayPal message
    function hidePaypalMessage() {
        const el = document.getElementById("paypalMessage");
        if (!el) return;

        el.style.display = "none";
        el.innerHTML = "";
    }

    // COMMON HELPERS
    function buildItems(cart) {
        return cart.checkout_options_items.map(item => ({
            name: item.name,
            sku: item.id,
            unit_amount: {
                currency_code: "USD",
                value: Number(item.price).toFixed(2)
            },
            quantity: item.qty.toString()
        }));
    }

    async function createOrder(mode = null) {
        try {
            hidePaypalMessage();

            const { cart, payer, billing } = checkoutData;

            const payload = {
                intent: "CAPTURE",
                purchase_units: [{
                    amount: {
                        currency_code: "USD",
                        value: Number(cart.grand_total).toFixed(2),
                        breakdown: {
                            item_total: {
                                currency_code: "USD",
                                value: Number(cart.subtotal).toFixed(2)
                            },
                            shipping: {
                                currency_code: "USD",
                                value: Number(cart.shipping).toFixed(2)
                            },
                            handling: {
                                currency_code: "USD",
                                value: Number(cart.handling).toFixed(2)
                            },
                            tax_total: {
                                currency_code: "USD",
                                value: Number(cart.tax).toFixed(2)
                            }
                        }
                    },
                    items: buildItems(cart),
                    shipping: {
                        name: {
                            full_name: `${payer.firstname} ${payer.lastname}`
                        },
                        address: {
                            address_line_1: billing.street,
                            admin_area_2: billing.city,
                            admin_area_1: billing.state,
                            postal_code: billing.zip,
                            country_code: billing.countrycode
                        }
                    }
                }]
            };

            // PAYMENT SOURCE HANDLING
            if (mode === "paypal" || mode === "paylater") {
                payload.payment_source = {
                    paypal: {
                        experience_context: {
                            user_action: "PAY_NOW"
                        }
                    }
                };
            }

            if (mode === "venmo") {
                payload.payment_source = {
                    venmo: {
                        experience_context: {
                            user_action: "PAY_NOW"
                        }
                    }
                };
            }

            const res = await fetch("../../core/paypal-api.php?action=ae_create_order", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!data.order_id) {
                throw new Error(data.message || "Unable to create order");
            }

            return data.order_id;

        } catch (err) {
            showPaypalMessage(err.message, "error");
            throw err;
        }
    }

    async function captureOrder(orderId, mode) {
        try {
            const res = await fetch("../../core/paypal-api.php?action=ae_capture_order", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: orderId })
            });

            const data = await res.json();

            if (data.status === "COMPLETED") {
                window.location.href = `getOrder.php?payment_mode=${mode}&order_id=${orderId}`;
            } else {
                throw new Error("Payment capture failed.");
            }
        } catch (err) {
            showPaypalMessage(err.message, "error");
        }
    }

    // INIT PAYPAL SDK
    let sdkInstance;
    try {
        const res = await fetch("../../core/paypal-api.php?action=ae_client_token");
        const { token } = await res.json();

        sdkInstance = await paypal.createInstance({
            clientToken: token,
            components: ["paypal-payments", "paypal-guest-payments", "venmo-payments", "card-fields"],
            pageType: "checkout"
        });
    } catch (err) {
        showPaypalMessage("SDK initialization failed", "error");
        return;
    }

    const methods = await sdkInstance.findEligibleMethods({
        currencyCode: "USD"
    });

    // PAYPAL BUTTON
    if (methods.isEligible("paypal") && paypalBtn) {
        paypalBtn.removeAttribute("hidden");
        const session = sdkInstance.createPayPalOneTimePaymentSession({
            async onApprove(data) {
                captureOrder(data.orderId, "paypal");
            },
            onError: e => showPaypalMessage(e.message, "error"),
            onCancel: () => location.reload()
        });

        paypalBtn.addEventListener("click", async () => {
            await session.start(
                { presentationMode: "auto" },
                createOrder("paypal").then(id => ({ orderId: id }))
            );
        });
    }

    // PAYPAL GUEST CHECKOUT
    if (guestBtn) {
        guestBtn.removeAttribute("hidden");
        const session = sdkInstance.createPayPalGuestOneTimePaymentSession({
            onApprove: data => captureOrder(data.orderId, "guest"),
            onError: e => showPaypalMessage(e.message, "error"),
            onCancel: () => location.reload()
        });

        guestBtn.addEventListener("click", async () => {
            const promise = createOrder().then(id => ({ orderId: id }));
            await session.start({ presentationMode: "auto" }, promise);
        });
    }

    // PAY LATER
    if (methods.isEligible("paylater") && payLaterBtn) {
        const details = methods.getDetails("paylater");

        // Initialize PayLater Messaging
        const messagesInstance = sdkInstance.createPayPalMessages();

        payLaterBtn.productCode = details.productCode;
        payLaterBtn.countryCode = details.countryCode;
        payLaterBtn.removeAttribute("hidden");

        const session = sdkInstance.createPayLaterOneTimePaymentSession({
            onApprove: data => captureOrder(data.orderId, "paylater"),
            onError: e => showPaypalMessage(e.message, "error"),
            onCancel: () => location.reload()
        });

        payLaterBtn.addEventListener("click", async () => {
            await session.start(
                { presentationMode: "auto" },
                createOrder("paylater").then(id => ({ orderId: id }))
            );
        });
    }

    // VENMO
    if (methods.isEligible("venmo") && venmoBtn) {
        venmoBtn.removeAttribute("hidden");
        const session = sdkInstance.createVenmoOneTimePaymentSession({
            fundingSource: "venmo",
            onApprove: data => captureOrder(data.orderId, "venmo"),
            onError: e => showPaypalMessage(e.message, "error"),
            onCancel: () => location.reload()
        });

        venmoBtn.addEventListener("click", async () => {
            await session.start(
                { presentationMode: "auto" },
                createOrder("venmo").then(id => ({ orderId: id }))
            );
        });
    }

    // ACDC CARD FIELDS
    if (methods.isEligible("advanced_cards")) {
        const cardSession = sdkInstance.createCardFieldsOneTimePaymentSession();

        const number = cardSession.createCardFieldsComponent({
            type: "number",
            placeholder: "Card Number"
        });

        const expiry = cardSession.createCardFieldsComponent({
            type: "expiry",
            placeholder: "MM/YY"
        });

        const cvv = cardSession.createCardFieldsComponent({
            type: "cvv",
            placeholder: "CVV"
        });

        document.getElementById("paypal-card-fields-number").appendChild(number);
        document.getElementById("paypal-card-fields-expiry").appendChild(expiry);
        document.getElementById("paypal-card-fields-cvv").appendChild(cvv);

        payButton.addEventListener("click", async () => {
            try {
                payButton.disabled = true;
                payButton.innerText = "Processing...";

                const orderId = await createOrder();

                const { data, state } =
                    await cardSession.submit(orderId, {
                        contingencies: ["SCA_ALWAYS"]
                    });

                if (state === "succeeded") {
                    captureOrder(data.orderId, "acdc");
                } else {
                    throw new Error("Card payment failed.");
                }
            } catch (e) {
                showPaypalMessage(e.message, "error");
            } finally {
                payButton.disabled = false;
                payButton.innerText = "Place Order";
            }
        });
    }
});