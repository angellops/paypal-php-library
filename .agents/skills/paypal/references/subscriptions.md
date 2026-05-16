# Subscriptions (recurring billing)

For SaaS, memberships, anything that bills repeatedly. Three-tier hierarchy: Product → Plan → Subscription.

Docs: https://developer.paypal.com/docs/subscriptions/

## Data model

1. **Product** (Catalog) — what's being sold (e.g., "Video Streaming Service"). Created once, reused across plans. Has `type` (`SERVICE`, `DIGITAL`, `PHYSICAL`) and `category` (e.g., `SOFTWARE`).
2. **Plan** (Billing) — pricing + billing cycles + tax for a product. One product → many plans (Monthly, Annual, etc.).
3. **Subscription** — a specific subscriber's recurring agreement against a plan. Has lifecycle status, billing schedule, subscriber info.

## Step 1 — Create the product

```
POST /v1/catalogs/products
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

```json
{
  "name": "Video Streaming Service",
  "description": "Video streaming service",
  "type": "SERVICE",
  "category": "SOFTWARE",
  "image_url": "https://example.com/streaming.jpg",
  "home_url": "https://example.com/home"
}
```

Response (`201 Created`):
```json
{
  "id": "PROD-XYAB12ABSB7868434",
  "name": "Video Streaming Service",
  "type": "SERVICE",
  "category": "SOFTWARE",
  "create_time": "2024-01-10T21:20:49Z",
  "links": [...]
}
```

## Step 2 — Create the plan

```
POST /v1/billing/plans
```

Trial + regular cycles, with a setup fee:
```json
{
  "product_id": "PROD-XYAB12ABSB7868434",
  "name": "Video Streaming Service Plan",
  "description": "Basic plan",
  "status": "ACTIVE",
  "billing_cycles": [
    {
      "frequency": { "interval_unit": "MONTH", "interval_count": 1 },
      "tenure_type": "TRIAL",
      "sequence": 1,
      "total_cycles": 2,
      "pricing_scheme": { "fixed_price": { "value": "3", "currency_code": "USD" } }
    },
    {
      "frequency": { "interval_unit": "MONTH", "interval_count": 1 },
      "tenure_type": "REGULAR",
      "sequence": 3,
      "total_cycles": 12,
      "pricing_scheme": { "fixed_price": { "value": "10", "currency_code": "USD" } }
    }
  ],
  "payment_preferences": {
    "auto_bill_outstanding": true,
    "setup_fee": { "value": "10", "currency_code": "USD" },
    "setup_fee_failure_action": "CONTINUE",
    "payment_failure_threshold": 3
  },
  "taxes": { "percentage": "10", "inclusive": false }
}
```

Notes on the sample (verbatim from PayPal docs):
- `sequence` is the order across cycles. PayPal's own example skips from `1` to `3` — the docs are quirky here; sequences just need to be ordered.
- `total_cycles` — `0` means infinite (only legal for the final REGULAR cycle).
- `setup_fee_failure_action` — `CONTINUE` (proceed) or `CANCEL` (abort the subscription).
- `payment_failure_threshold` — N consecutive failures before the subscription auto-suspends.
- `taxes` — applied to every payment.

Plan status: `CREATED`, `INACTIVE`, `ACTIVE`. Activate with `POST /v1/billing/plans/{id}/activate`. Other plan endpoints:
- `GET /v1/billing/plans` — list
- `GET /v1/billing/plans/{id}` — show
- `PATCH /v1/billing/plans/{id}` — update
- `POST /v1/billing/plans/{id}/deactivate`
- `POST /v1/billing/plans/{id}/update-pricing-schemes` — change prices

Pricing models — `fixed_price` is shown above. PayPal also supports `tiered` (volume / graduated) pricing via different `pricing_scheme` shapes; reference: https://developer.paypal.com/docs/api/subscriptions/v1/

## Step 3a — Create subscription via JS SDK (browser, recommended)

The SDK handles the approval redirect for you. Update the script tag to include `vault=true&intent=subscription`:

```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&intent=subscription&vault=true"></script>

<div id="paypal-button-container"></div>
<script>
paypal.Buttons({
  createSubscription(data, actions) {
    return actions.subscription.create({
      plan_id: "P-5ML4271244454362WXNWU5NQ"
      // optional: subscriber info, application_context.return_url/cancel_url, custom_id
    });
  },
  onApprove(data) {
    // data.subscriptionID is the new subscription ID (form I-...)
    fetch(`/api/paypal/subscription-activated/${data.subscriptionID}`, { method: "POST" });
  }
}).render("#paypal-button-container");
</script>
```

`data.subscriptionID` matches the `id` returned by the server-side create endpoint. The plan must be created on the same client_id used in the SDK.

## Step 3b — Create subscription server-side

```
POST /v1/billing/subscriptions
Authorization: Bearer <ACCESS_TOKEN>
```

```json
{
  "plan_id": "P-5ML4271244454362WXNWU5NQ",
  "start_time": "2024-11-01T00:00:00Z",
  "quantity": "1",
  "subscriber": {
    "name": { "given_name": "John", "surname": "Doe" },
    "email_address": "customer@example.com"
  },
  "application_context": {
    "brand_name": "ACME Inc.",
    "locale": "en-US",
    "shipping_preference": "NO_SHIPPING",
    "user_action": "SUBSCRIBE_NOW",
    "payment_method": {
      "payer_selected": "PAYPAL",
      "payee_preferred": "IMMEDIATE_PAYMENT_REQUIRED"
    },
    "return_url": "https://example.com/return",
    "cancel_url": "https://example.com/cancel"
  }
}
```

Response: `status: "APPROVAL_PENDING"` and a HATEOAS `links` array. Find the link with `rel: "approve"` and redirect the subscriber to its `href`. After approval (and successful first payment), the subscription transitions to `ACTIVE`.

## Subscription lifecycle

Confirmed states: `APPROVAL_PENDING`, `ACTIVE`, `SUSPENDED`, `CANCELLED`, `EXPIRED`.

Transitions:
- New → `APPROVAL_PENDING` → buyer approves → first payment runs → `ACTIVE`.
- `ACTIVE` → suspend / cancel via API.
- After `payment_failure_threshold` consecutive failures → auto `SUSPENDED`.
- After natural end of all billing cycles → `EXPIRED`.

## Manage a subscription

```
GET    /v1/billing/subscriptions/{id}
PATCH  /v1/billing/subscriptions/{id}                    (limited patches: subscriber, custom_id, etc.)
POST   /v1/billing/subscriptions/{id}/suspend            (body: {"reason":"..."})
POST   /v1/billing/subscriptions/{id}/cancel             (body: {"reason":"..."})
POST   /v1/billing/subscriptions/{id}/activate           (resume from SUSPENDED)
POST   /v1/billing/subscriptions/{id}/revise             (change plan / pricing — returns approval URL)
POST   /v1/billing/subscriptions/{id}/capture            (manually capture an outstanding amount)
GET    /v1/billing/subscriptions/{id}/transactions       (?start_time=...&end_time=...)
```

`reason` is a 1-128 char string. All three (suspend/cancel/activate) return `204 No Content`.

`revise` returns a new approval link if the change requires buyer consent (e.g., higher price, new plan). Lower prices typically don't require re-approval but check the response for an `approve` link.

## Webhooks for subscriptions

Subscribe to:
- `BILLING.SUBSCRIPTION.CREATED`
- `BILLING.SUBSCRIPTION.ACTIVATED` ← service starts here
- `BILLING.SUBSCRIPTION.UPDATED`
- `BILLING.SUBSCRIPTION.CANCELLED`
- `BILLING.SUBSCRIPTION.SUSPENDED`
- `BILLING.SUBSCRIPTION.EXPIRED`
- `BILLING.SUBSCRIPTION.PAYMENT.FAILED` ← dunning trigger
- `PAYMENT.SALE.COMPLETED` ← each recurring charge captures (subscriptions use the legacy "sale" event for individual payments)
- `PAYMENT.SALE.REFUNDED`
- `PAYMENT.SALE.REVERSED`

Plan events: `BILLING.PLAN.CREATED`, `UPDATED`, `ACTIVATED`, `DEACTIVATED`, `PRICING-CHANGE.ACTIVATED`.

See `webhooks.md` for verification and handler patterns.

## Common pitfalls

- **Plan + SDK client_id must match.** A plan created under one app's client_id can't be paid for by buttons rendered with another app's client_id. Create plans for both sandbox and live separately.
- **Plan must be ACTIVE before referenced.** A `CREATED` plan can't accept subscriptions.
- **`vault=true` and `intent=subscription` are both required** in the JS SDK script for `createSubscription` to work.
- **Don't poll for status — listen for webhooks.** Subscriptions live for months/years; webhook-driven sync is the only practical model.
- **Test the dunning path.** Use sandbox negative testing to force a failed payment and confirm your `BILLING.SUBSCRIPTION.PAYMENT.FAILED` handler degrades the user's access.
- **Trial → regular transitions use the `sequence` field**, not dates. PayPal calculates dates from `start_time` + cycle count.

## Reference URLs

- Subscriptions overview: https://developer.paypal.com/docs/subscriptions/
- Integration walkthrough: https://developer.paypal.com/docs/subscriptions/integrate/
- Catalog Products v1: https://developer.paypal.com/docs/api/catalog-products/v1/
- Subscriptions v1 API: https://developer.paypal.com/docs/api/subscriptions/v1/
- JS SDK reference (createSubscription): https://developer.paypal.com/sdk/js/reference/
