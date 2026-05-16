# Advanced (Expanded) Checkout

PayPal recently rebranded "Advanced Checkout" as **"Expanded Checkout"** — the URL `/docs/checkout/advanced/` still resolves and the integration is unchanged. Difference from Standard Checkout (`checkout-standard.md`):

- **Card form is rendered inline on the merchant site** (in PayPal-hosted iframes, so the merchant's PCI scope stays at SAQ A).
- Adds **Apple Pay**, **Google Pay**, and direct card acceptance alongside the PayPal button.
- 3D Secure (SCA) is supported via `contingencies`.

**Eligibility**: requires "Complete production onboarding" + "Request Expanded Credit and Debit Card Payments" via the PayPal dashboard. Not all countries support it. Confirm before promising it to the user.

Docs: https://developer.paypal.com/docs/checkout/advanced/integrate

## Card Fields component

The modern card-input integration (the older `hosted-fields` is legacy — only use it if maintaining old code).

Script tag — request the `card-fields` component:
```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD&components=buttons,card-fields"></script>
```

Render fields:
```html
<div id="card-name-field-container"></div>
<div id="card-number-field-container"></div>
<div id="card-expiry-field-container"></div>
<div id="card-cvv-field-container"></div>
<button id="card-pay-button">Pay</button>

<script>
const cardFields = paypal.CardFields({
  createOrder: async () => {
    const r = await fetch("/api/paypal/create-order", { method: "POST" });
    return (await r.json()).id;
  },
  onApprove: async (data) => {
    const r = await fetch(`/api/paypal/capture-order/${data.orderID}`, { method: "POST" });
    const details = await r.json();
    // success path
  },
  onError: (err) => console.error(err),
  style: {
    input: { "font-size": "16px", color: "#333" },
    ":focus": { color: "blue" },
    ".invalid": { color: "red" }
  }
});

if (cardFields.isEligible()) {
  cardFields.NameField().render("#card-name-field-container");     // optional
  cardFields.NumberField().render("#card-number-field-container"); // required
  cardFields.ExpiryField().render("#card-expiry-field-container"); // required
  cardFields.CVVField().render("#card-cvv-field-container");       // required

  document.getElementById("card-pay-button").addEventListener("click", () => {
    cardFields.submit().catch(console.error);
  });
}
</script>
```

Per-field methods: `addClass`, `removeClass`, `clear`, `focus`, `render`, `setAttribute`, `removeAttribute`, `setMessage`. Optional `inputEvents`: `onChange`, `onFocus`, `onBlur`, `onInputSubmitRequest`.

`getState()` returns validity + tokenized card metadata; `isEligible()` tells you whether the buyer's region/funding allows card fields.

## 3D Secure (SCA)

For SCA-required regions (most of EU, UK), set the contingency on the order's payment_source.card:

```json
{
  "intent": "CAPTURE",
  "purchase_units": [...],
  "payment_source": {
    "card": {
      "attributes": {
        "verification": { "method": "SCA_WHEN_REQUIRED" }
      }
    }
  }
}
```

Values: `SCA_ALWAYS` (always force 3DS) or `SCA_WHEN_REQUIRED` (apply only when the issuer demands it; recommended).

After capture, inspect `purchase_units[0].payments.captures[0].processor_response` and the auth_result to know whether 3DS challenged.

## Server-side card capture (no JS SDK)

If you collect card data server-side (you'd need full PCI compliance — typically only platforms with PCI Level 1 do this), populate `payment_source.card` directly on Create Order:

```json
{
  "intent": "CAPTURE",
  "purchase_units": [{ "amount": { "currency_code": "USD", "value": "100.00" } }],
  "payment_source": {
    "card": {
      "name": "Jane Doe",
      "number": "4111111111111111",
      "expiry": "2030-12",
      "security_code": "123",
      "billing_address": {
        "address_line_1": "123 Main St",
        "admin_area_2": "San Jose",
        "admin_area_1": "CA",
        "postal_code": "95131",
        "country_code": "US"
      }
    }
  }
}
```

Then `/confirm-payment-source` if needed (for 3DS), then `/capture`. Most integrators use Card Fields instead to avoid PCI scope.

## Apple Pay

Add `applepay` to components:
```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD&components=buttons,applepay"></script>
```

Render the Apple Pay button:
```javascript
const applePay = paypal.Applepay();
const config = await applePay.config();
if (config.isEligible) {
  // render <apple-pay-button> element
  // on click → applePay.confirmOrder({ orderId, token, billingContact, shippingContact })
}
```

You also need to validate your domain with Apple (host `apple-developer-merchantid-domain-association` at `/.well-known/`) — the dashboard walks you through it.

Apple Pay docs: https://developer.paypal.com/docs/checkout/apm/apple-pay/

## Google Pay

Similar pattern — Google Pay flow uses PayPal's REST API with `payment_source.google_pay`. Docs: https://developer.paypal.com/docs/checkout/apm/google-pay/

(Google Pay is not in the documented SDK `components` list I verified — request it via the loaded SDK and PayPal docs at integration time.)

## Mobile Card Fields (iOS / Android)

For native mobile, see `mobile-sdks.md` — the iOS SDK's `CardClient.approveOrder` and Android's `CardClient.approveOrder(activity, cardRequest)` are the equivalents.

## Reference URLs

- Advanced Checkout integration: https://developer.paypal.com/docs/checkout/advanced/integrate
- Card Fields reference: https://developer.paypal.com/sdk/js/reference/#card-fields
- Apple Pay: https://developer.paypal.com/docs/checkout/apm/apple-pay/
- Google Pay: https://developer.paypal.com/docs/checkout/apm/google-pay/
