# Standard Checkout

PayPal-hosted payment page. Buyer is redirected (or sees a popup) to PayPal, signs in or pays as guest, returns to your site. **No PCI scope** — PayPal handles the card form. This is the default for most "Pay with PayPal" integrations.

Standard Checkout exposes: PayPal balance, Pay Later (BNPL), Venmo (US), and PayPal-handled credit/debit cards (no card form on the merchant site).

For inline credit-card fields rendered on the merchant site, see `checkout-advanced.md`.

Docs: https://developer.paypal.com/docs/checkout/standard/integrate

## Architecture

Three pieces:

1. **Frontend (browser)** — loads PayPal JS SDK, renders Smart Buttons. The buttons call back to your server when the buyer clicks.
2. **Server** — creates the PayPal order via REST API (the "create" endpoint), and captures it after buyer approval (the "capture" endpoint). Holds the client secret.
3. **PayPal** — hosts the approval flow.

Flow:
```
Buyer clicks PayPal button
  → Frontend calls your server's "create order" endpoint
  → Server calls POST /v2/checkout/orders → returns order ID
  → Frontend hands order ID to PayPal SDK → PayPal opens approval flow
  → Buyer approves
  → Frontend calls your server's "capture order" endpoint
  → Server calls POST /v2/checkout/orders/{id}/capture
  → Server confirms success and fulfills the order
```

## Frontend: JS SDK

Script tag (the recommended modern shape):
```html
<script
  src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD&components=buttons&enable-funding=venmo,paylater"
  data-sdk-integration-source="developer-studio"
></script>
```

Common query parameters (full reference: https://developer.paypal.com/sdk/js/configuration/):

| Parameter | Default | Notes |
|---|---|---|
| `client-id` | required | Your PayPal client ID. Use `test` for the docs sandbox. |
| `currency` | `USD` | ISO 4217. Must match your order's currency. |
| `intent` | `capture` | `capture`, `authorize`, `subscription`, `tokenize` |
| `components` | `buttons` | Comma-separated. Common: `buttons`, `messages`, `marks`, `card-fields`, `applepay`, `funding-eligibility` |
| `enable-funding` / `disable-funding` | none | Comma-separated funding source names: `card`, `credit`, `paylater`, `venmo`, `bancontact`, `blik`, `eps`, `ideal`, `mercadopago`, `mybank`, `p24`, `sepa`, `sofort` |
| `buyer-country` | auto | Sandbox-only override (e.g., `US`, `GB`, `DE`). |
| `locale` | auto | E.g., `en_US`, `fr_FR`. |
| `debug` | `false` | Verbose console logging. |
| `vault` | `false` | Save payment info for later (required for subscriptions). |

Useful `data-*` script attributes:
- `data-csp-nonce` — CSP nonce for strict CSP setups.
- `data-partner-attribution-id` — partner BN code (multi-party only).
- `data-page-type` — `product-listing`, `cart`, `checkout`, etc. — improves PayPal's behavior tuning.
- `data-client-token` — passes a PayPal-generated client token (used for Vault, Cards, etc.).

Render the buttons:
```html
<div id="paypal-button-container"></div>
<script>
  paypal.Buttons({
    async createOrder() {
      const res = await fetch("/api/paypal/create-order", { method: "POST" });
      const order = await res.json();
      return order.id;   // PayPal order ID returned by your server
    },
    async onApprove(data) {
      const res = await fetch(`/api/paypal/capture-order/${data.orderID}`, { method: "POST" });
      const details = await res.json();
      // details.payer.name.given_name, details.purchase_units[0].payments.captures[0].id, etc.
    },
    onCancel(data) { /* buyer closed the window */ },
    onError(err)   { /* SDK or network error */ }
  }).render("#paypal-button-container");
</script>
```

Style options (verbatim from JS SDK reference):
```javascript
paypal.Buttons({
  style: {
    layout: 'vertical',   // 'vertical' | 'horizontal'
    color:  'gold',       // 'gold' | 'blue' | 'silver' | 'white' | 'black'
    shape:  'rect',       // 'rect' | 'pill' | 'sharp'
    label:  'paypal',     // 'paypal' | 'checkout' | 'buynow' | 'pay' | 'installment'
    height: 40,           // 25-55
    tagline: true
  }
});
```

Other callbacks:
- `onShippingAddressChange(data, actions)` and `onShippingOptionsChange(data, actions)` — replace the deprecated `onShippingChange`. Use to recompute shipping/tax when the buyer picks an address.
- `onClick(data, actions)` — pre-flight validation (e.g., check that they accepted ToS).
- `onInit(data, actions)` — runs once when the buttons are ready; pair with `actions.disable()/enable()` to gate clicks.

## Server: Orders v2

Auth: get a token first (see SKILL.md "Universal foundations").

### Create order

```
POST /v2/checkout/orders
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
PayPal-Request-Id: <unique UUID>
```

Minimal body:
```json
{
  "intent": "CAPTURE",
  "purchase_units": [{
    "amount": { "currency_code": "USD", "value": "100.00" }
  }]
}
```

Fuller body with shipping, items, and modern `experience_context`:
```json
{
  "intent": "CAPTURE",
  "purchase_units": [{
    "reference_id": "cart-7842",
    "description": "Premium subscription - 1 year",
    "amount": {
      "currency_code": "USD",
      "value": "120.00",
      "breakdown": {
        "item_total":  { "currency_code": "USD", "value": "100.00" },
        "tax_total":   { "currency_code": "USD", "value": "10.00" },
        "shipping":    { "currency_code": "USD", "value": "10.00" }
      }
    },
    "items": [{
      "name": "Premium Plan",
      "quantity": "1",
      "unit_amount": { "currency_code": "USD", "value": "100.00" },
      "category": "DIGITAL_GOODS"
    }]
  }],
  "payment_source": {
    "paypal": {
      "experience_context": {
        "brand_name": "ACME Inc.",
        "landing_page": "LOGIN",
        "shipping_preference": "NO_SHIPPING",
        "user_action": "PAY_NOW",
        "return_url": "https://example.com/return",
        "cancel_url": "https://example.com/cancel"
      }
    }
  }
}
```

Response (minimal `Prefer: return=minimal` default):
```json
{
  "id": "5O190127TN364715T",
  "status": "CREATED",
  "links": [
    { "href": "https://api-m.paypal.com/v2/checkout/orders/5O190127TN364715T",
      "rel": "self",   "method": "GET" },
    { "href": "https://www.paypal.com/checkoutnow?token=5O190127TN364715T",
      "rel": "payer-action", "method": "GET" }
  ]
}
```

The `id` is what you return to the JS SDK. The `payer-action` link is what you'd redirect to if you weren't using the SDK (e.g., a server-side redirect flow).

### Capture order

```
POST /v2/checkout/orders/{id}/capture
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
PayPal-Request-Id: <unique UUID>
Prefer: return=representation
```

Empty JSON body `{}`. Send `Prefer: return=representation` if you want the full capture object back (recommended):
```json
{
  "id": "5O190127TN364715T",
  "status": "COMPLETED",
  "purchase_units": [{
    "payments": {
      "captures": [{
        "id": "3C679366HH908993F",
        "status": "COMPLETED",
        "amount": { "currency_code": "USD", "value": "100.00" },
        "final_capture": true,
        "seller_protection": {
          "status": "ELIGIBLE",
          "dispute_categories": ["ITEM_NOT_RECEIVED", "UNAUTHORIZED_TRANSACTION"]
        },
        "seller_receivable_breakdown": {
          "gross_amount": { "currency_code": "USD", "value": "100.00" },
          "paypal_fee":   { "currency_code": "USD", "value": "3.00" },
          "net_amount":   { "currency_code": "USD", "value": "97.00" }
        }
      }]
    }
  }],
  "payer": { "email_address": "buyer@example.com", "payer_id": "QYR5Z8XDVJNXQ" }
}
```

Persist `purchase_units[0].payments.captures[0].id` — that's the **capture ID**, used for refunds (`POST /v2/payments/captures/{capture_id}/refund`).

### Order statuses

`CREATED` → `SAVED` → `APPROVED` → `COMPLETED` (or `VOIDED`, or `PAYER_ACTION_REQUIRED`). You can only capture an order in `APPROVED` status. The JS SDK transitions you from `CREATED` to `APPROVED` when the buyer approves.

### intent: CAPTURE vs AUTHORIZE

- **`CAPTURE`** — single-step. Funds move when you call `/capture`. Use for shipped-immediately or digital goods.
- **`AUTHORIZE`** — two-step. `/authorize` reserves funds for a 3-day honor period (extendable to 29 days via `/reauthorize`); `/capture` actually moves the money later. Use when fulfillment is delayed.

The two-step flow uses different endpoints:
```
POST /v2/checkout/orders/{id}/authorize   → returns authorization ID
POST /v2/payments/authorizations/{auth_id}/capture
POST /v2/payments/authorizations/{auth_id}/reauthorize
POST /v2/payments/authorizations/{auth_id}/void
```

### Refunds

```
POST /v2/payments/captures/{capture_id}/refund
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

Empty body `{}` for a full refund. Partial refund:
```json
{
  "amount": { "currency_code": "USD", "value": "25.00" },
  "note_to_payer": "Partial refund as requested",
  "invoice_id": "INV-2024-001"
}
```

### Get order details

`GET /v2/checkout/orders/{id}` — useful for reconciling state if the buyer's browser disconnects between approval and capture.

## Server-side, no JS SDK (server-side redirect flow)

When you can't run the JS SDK (server-rendered checkout, email link, hardware terminal):

1. Server: `POST /v2/checkout/orders` with `intent` and `purchase_units` (include `experience_context.return_url` and `cancel_url` under `payment_source.paypal`).
2. Server: redirect the buyer to the `payer-action` HATEOAS link from the response.
3. Buyer approves on PayPal, gets sent to your `return_url?token=ORDER_ID&PayerID=...`.
4. Server: `POST /v2/checkout/orders/{ORDER_ID}/capture`.

## Save payment method for later (Vault)

For one-click reorder or stored-on-file scenarios, set `payment_source.paypal.attributes.vault.store_in_vault = "ON_SUCCESS"` on Create Order. After capture, PayPal returns a `vault.id` — use it on future orders as `payment_source.paypal.vault_id` to skip the approval step.

Full vault docs: https://developer.paypal.com/docs/multiparty/checkout/save-payment-methods/ (the same API powers single-merchant vaulting).

## Common errors

| Error name | Cause |
|---|---|
| `INVALID_REQUEST` (400) | Schema violation in body |
| `UNPROCESSABLE_ENTITY` (422) with `INSTRUMENT_DECLINED` | Buyer's funding source declined; restart the JS button |
| `UNPROCESSABLE_ENTITY` (422) with `ORDER_NOT_APPROVED` | Tried to capture before buyer approved |
| `UNPROCESSABLE_ENTITY` (422) with `PAYER_ACTION_REQUIRED` | Need to send buyer back to PayPal (e.g., for 3DS) |
| `RESOURCE_NOT_FOUND` (404) | Wrong order ID, or queried live with a sandbox token |

For `INSTRUMENT_DECLINED` on the JS SDK, call `actions.restart()` inside `onApprove` to send the buyer back to choose another funding source.

## Reference URLs

- Standard Checkout overview: https://developer.paypal.com/docs/checkout/standard/
- Integration: https://developer.paypal.com/docs/checkout/standard/integrate
- Customize buttons: https://developer.paypal.com/docs/checkout/standard/customize/
- Orders v2 API: https://developer.paypal.com/docs/api/orders/v2/
- Payments v2 API (refunds, voids): https://developer.paypal.com/docs/api/payments/v2/
- JS SDK reference: https://developer.paypal.com/sdk/js/reference/
- JS SDK configuration: https://developer.paypal.com/sdk/js/configuration/
