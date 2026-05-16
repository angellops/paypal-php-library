# Webhooks

Webhooks are HTTPS POSTs PayPal sends to your endpoint when something interesting happens — payment captured, subscription cancelled, dispute opened, etc. They're how you keep your system in sync with PayPal's view of the world.

Docs: https://developer.paypal.com/api/rest/webhooks/

## Setup

A webhook is created once per app (you can have up to 10 URLs per app). Two ways to create:

- **Dashboard** — fastest for sandbox testing. Apps & Credentials → click your app → Webhooks → Add Webhook → enter URL + check event types.
- **API** — required if your URL changes per environment, or if you want to subscribe programmatically.

```
POST /v1/notifications/webhooks
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

Body:
```json
{
  "url": "https://example.com/paypal/webhook",
  "event_types": [
    { "name": "PAYMENT.CAPTURE.COMPLETED" },
    { "name": "PAYMENT.CAPTURE.REFUNDED" },
    { "name": "CHECKOUT.ORDER.APPROVED" }
  ]
}
```

Subscribe to all events with `[{"name": "*"}]` — useful in dev, noisy in prod.

Other endpoints:
- `GET /v1/notifications/webhooks` — list all webhooks
- `GET /v1/notifications/webhooks/{id}` — get one
- `PATCH /v1/notifications/webhooks/{id}` — update (JSON Patch format)
- `DELETE /v1/notifications/webhooks/{id}` — delete

JSON Patch update example (change URL and event list):
```json
[
  { "op": "replace", "path": "/url", "value": "https://example.com/webhook_v2" },
  { "op": "replace", "path": "/event_types", "value": [{ "name": "PAYMENT.SALE.REFUNDED" }] }
]
```

## Event payload

Every event has the same envelope:
```json
{
  "id": "8PT597110X687430LKGECATA",
  "create_time": "2024-06-25T21:41:28.000Z",
  "resource_type": "capture",
  "event_version": "1.0",
  "event_type": "PAYMENT.CAPTURE.COMPLETED",
  "summary": "Payment completed for $100.00 USD",
  "resource_version": "2.0",
  "resource": { /* event-specific resource — a capture, subscription, dispute, etc. */ },
  "links": [
    { "href": "https://api-m.paypal.com/v1/notifications/webhooks-events/8PT.../resend",
      "rel": "resend", "method": "POST" }
  ]
}
```

Key fields to dispatch on: `event_type` (the routing key) and `resource.id` (your business key — capture ID, subscription ID, dispute ID, etc.).

## Common event types

A subset — full list at https://developer.paypal.com/api/rest/webhooks/event-names/. Subscribe only to what you need.

**Checkout / Orders**
- `CHECKOUT.ORDER.APPROVED` — buyer approved, you can now capture
- `CHECKOUT.ORDER.COMPLETED` — order fully captured
- `PAYMENT.CAPTURE.COMPLETED` — funds moved (the one most apps care about)
- `PAYMENT.CAPTURE.DENIED` — capture rejected
- `PAYMENT.CAPTURE.PENDING` — capture awaiting review
- `PAYMENT.CAPTURE.REFUNDED` — refund issued (full or partial)
- `PAYMENT.CAPTURE.REVERSED` — chargeback or admin reversal

**Subscriptions / Billing**
- `BILLING.SUBSCRIPTION.CREATED`
- `BILLING.SUBSCRIPTION.ACTIVATED` — first payment succeeded; service starts
- `BILLING.SUBSCRIPTION.UPDATED`
- `BILLING.SUBSCRIPTION.CANCELLED`
- `BILLING.SUBSCRIPTION.SUSPENDED`
- `BILLING.SUBSCRIPTION.EXPIRED` — natural end of billing
- `BILLING.SUBSCRIPTION.PAYMENT.FAILED` — recurring payment failed; usually combined with a dunning state
- `PAYMENT.SALE.COMPLETED` — recurring payment captured (subscriptions use the legacy "sale" event)
- `BILLING.PLAN.ACTIVATED` / `DEACTIVATED` / `PRICING-CHANGE.ACTIVATED`

**Invoicing**
- `INVOICING.INVOICE.CREATED`
- `INVOICING.INVOICE.UPDATED`
- `INVOICING.INVOICE.SCHEDULED`
- `INVOICING.INVOICE.PAID`
- `INVOICING.INVOICE.CANCELLED`
- `INVOICING.INVOICE.REFUNDED`

**Payouts**
- `PAYMENT.PAYOUTSBATCH.SUCCESS` / `PROCESSING` / `DENIED`
- `PAYMENT.PAYOUTS-ITEM.SUCCEEDED`
- `PAYMENT.PAYOUTS-ITEM.FAILED`
- `PAYMENT.PAYOUTS-ITEM.UNCLAIMED` — recipient didn't claim within 30 days
- `PAYMENT.PAYOUTS-ITEM.RETURNED` — auto-refunded after 30 days unclaimed
- `PAYMENT.PAYOUTS-ITEM.REFUNDED`
- `PAYMENT.PAYOUTS-ITEM.HELD`
- `PAYMENT.PAYOUTS-ITEM.BLOCKED`
- `PAYMENT.PAYOUTS-ITEM.CANCELED`

**Disputes**
- `CUSTOMER.DISPUTE.CREATED`
- `CUSTOMER.DISPUTE.UPDATED`
- `CUSTOMER.DISPUTE.RESOLVED`
- `RISK.DISPUTE.CREATED` — superseded by `CUSTOMER.DISPUTE.CREATED`; don't use for new code

**Multi-party / merchant onboarding**
- `MERCHANT.ONBOARDING.COMPLETED` — seller has finished onboarding
- `MERCHANT.PARTNER-CONSENT.REVOKED` — seller removed your platform's permissions
- `CUSTOMER.MERCHANT-INTEGRATION.SELLER-CONSENT-GRANTED` / `SELLER-EMAIL-CONFIRMED` / `CAPABILITY-UPDATED`

**Vault**
- `VAULT.PAYMENT-TOKEN.CREATED`
- `VAULT.PAYMENT-TOKEN.DELETED`

## Signature verification (CRITICAL)

Webhook payloads can be spoofed. Verify before trusting. Two approaches: PayPal's verification endpoint (easy) or local cryptographic verification (faster, no extra API call).

### Headers PayPal sends

Per the integration guide:
- `paypal-transmission-id` — unique transmission ID
- `paypal-transmission-time` — ISO 8601 timestamp
- `paypal-transmission-sig` — Base64-encoded signature
- `paypal-cert-url` — URL to PayPal's signing certificate
- `paypal-auth-algo` — signing algorithm (`SHA256withRSA`)

### Approach 1 — Postback to PayPal (recommended for most users)

```
POST /v1/notifications/verify-webhook-signature
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

Body — every field comes from either the request headers or the original event body:
```json
{
  "auth_algo":         "SHA256withRSA",
  "cert_url":          "https://api.paypal.com/v1/notifications/certs/CERT-...",
  "transmission_id":   "69cd13f0-d67a-11e5-baa3-778b53f4ae55",
  "transmission_sig":  "lmI95Jx3Y9nhR5SJWlHVIWpg4AgFk7n9bCHSRxbrd8A...",
  "transmission_time": "2024-06-18T20:01:35Z",
  "webhook_id":        "1JE4291016473214C",
  "webhook_event":     { /* the entire received envelope, byte-exact */ }
}
```

Response: `{ "verification_status": "SUCCESS" }` or `"FAILURE"`.

**Critical:** `webhook_event` must be the parsed JSON of the body you received, exactly as PayPal sent it. If you re-serialize (especially with key reordering or whitespace changes), verification will fail. Best practice: keep the raw body string in your handler, parse a copy for processing, and pass the parsed-then-restringified copy here. Better: most languages have a way to pass the parsed object directly.

`webhook_id` is the ID of the webhook subscription you created (from `POST /v1/notifications/webhooks` or the dashboard) — store it as config.

### Approach 2 — Local CRC32 verification

Faster (no PayPal round-trip) but more fiddly. PayPal documents this for users who can't tolerate the extra API call.

Construct the message: `transmission_id|transmission_time|webhook_id|crc32(body)` (CRC32 of the raw body as a decimal integer). Verify the `paypal-transmission-sig` (Base64-decoded) against that message using PayPal's public key fetched from `paypal-cert-url` and the algorithm from `paypal-auth-algo`.

Cache the cert by URL — PayPal rotates them but they're stable for hours/days.

### Sample handler skeleton (Node + Express)

```javascript
import express from "express";
import fetch from "node-fetch";

const app = express();
app.use("/paypal/webhook", express.raw({ type: "application/json" })); // keep raw body

const WEBHOOK_ID = process.env.PAYPAL_WEBHOOK_ID;

async function getAccessToken() { /* see SKILL.md "Universal foundations" */ }

app.post("/paypal/webhook", async (req, res) => {
  const body = req.body.toString("utf8");
  const event = JSON.parse(body);

  const verifyRes = await fetch(
    "https://api-m.paypal.com/v1/notifications/verify-webhook-signature",
    {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${await getAccessToken()}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        auth_algo:         req.headers["paypal-auth-algo"],
        cert_url:          req.headers["paypal-cert-url"],
        transmission_id:   req.headers["paypal-transmission-id"],
        transmission_sig:  req.headers["paypal-transmission-sig"],
        transmission_time: req.headers["paypal-transmission-time"],
        webhook_id:        WEBHOOK_ID,
        webhook_event:     event,
      }),
    }
  );
  const { verification_status } = await verifyRes.json();
  if (verification_status !== "SUCCESS") {
    return res.status(400).send("invalid signature");
  }

  // Dispatch on event_type
  switch (event.event_type) {
    case "PAYMENT.CAPTURE.COMPLETED":
      // event.resource is the capture object
      break;
    case "BILLING.SUBSCRIPTION.CANCELLED":
      // event.resource is the subscription object
      break;
  }

  // Always 200 quickly; do work async if it might take long
  res.status(200).send();
});
```

## Retry behavior

Per docs: "Any non-2xx status code will cause PayPal to reattempt delivery up to **25 times over the course of 3 days** or until it receives a 2xx success code." After 3 days the delivery is marked Failed.

Implications:
- Always return 2xx **fast** (under 5s). Defer real work to a queue if needed; PayPal doesn't care about the body.
- Idempotency on your side is mandatory. PayPal can deliver the same event twice (network blip + retry), and after a downtime you'll get backfilled. Dedupe by `event.id`.
- Failed deliveries are visible and can be manually resent: https://dashboard.paypal.com/webhooks/sandbox or `/live`.

## Simulator (sandbox)

Tool: https://developer.paypal.com/dashboard/webhooksSimulator/

Lets you POST a mock event of any supported `event_type` to your URL. Useful for testing handlers before real transactions are flowing.

**Critical limitation**: simulated events use `webhook_id = "WEBHOOK_ID"` (literal placeholder) and **fail** the postback verification. Either:
- bypass verification when `req.headers["paypal-transmission-id"]` looks like a simulator ID, OR
- only test event-type routing with the simulator, not signature verification.

## Common pitfalls

- **Mixing sandbox and live** — sandbox events are signed by sandbox certs; if you point a sandbox webhook at a live verification call, it'll always say FAILURE. Each environment needs its own webhook subscription.
- **Re-serializing the body** before verification corrupts the signature. Hold the raw bytes.
- **Returning 5xx slowly** — PayPal's 5s timeout will trigger retries. Acknowledge first, work later.
- **Forgetting idempotency** — store `event.id` and check it before processing. PayPal explicitly may redeliver.
- **Subscribing to `*` in production** — gets you events you don't handle, bloats logs, and risks accidentally acting on something you shouldn't. Subscribe by intent.
