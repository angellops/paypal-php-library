# Payouts (sending money out)

When the user wants to **send** money to many recipients — affiliate commissions, freelancer payouts, rewards, refunds outside an order context, gig-economy payouts, etc.

PayPal offers two payout products:

- **Standard Payouts** — REST API, 20+ currencies, 96 countries/regions, pays to PayPal and Venmo wallets. This page covers it.
- **Advanced Payouts** — enterprise-grade via Hyperwallet. 50+ currencies, 240+ countries, adds checks, cash pickup, drop-in UI, embedded portal. Not REST-based; requires PayPal partner setup.

Docs: https://developer.paypal.com/docs/payouts/

## Standard Payouts: Create batch

```
POST /v1/payments/payouts
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

Minimal body (verbatim from API ref):
```json
{
  "sender_batch_header": {
    "sender_batch_id": "Payouts_2024_100007",
    "email_subject": "You have a payout!",
    "email_message": "You have received a payout!"
  },
  "items": [
    {
      "recipient_type": "EMAIL",
      "amount": { "value": "9.87", "currency": "USD" },
      "note": "Thanks for your patronage!",
      "sender_item_id": "201403140001",
      "receiver": "example@example.com"
    }
  ]
}
```

Response `201 Created`:
```json
{
  "batch_header": {
    "payout_batch_id": "FYXMPS3VWMRBA",
    "batch_status": "PENDING",
    "sender_batch_header": { ... }
  },
  "links": [{ "href": ".../v1/payments/payouts/FYXMPS3VWMRBA", "rel": "self", "method": "GET" }]
}
```

`payout_batch_id` is your handle — store it. The batch immediately reports `PENDING`; you'll get webhooks (or poll) as items process.

### Constraints

- **Max 15,000 items per batch.**
- **Single currency per call** (the whole `items[].amount.currency` must match).
- **`sender_batch_id` is dedup'd for 30 days** — PayPal will reject a second batch with the same `sender_batch_id`. Use a UUID or your internal batch number.
- **Unclaimed items auto-refund after 30 days.** Recipients get an email with a claim link; if they don't act, PayPal returns the funds and fires `PAYMENT.PAYOUTS-ITEM.UNCLAIMED` then `PAYMENT.PAYOUTS-ITEM.RETURNED`.

### Recipient types

`items[].recipient_type` enum:
- `EMAIL` — unencrypted email (max 127 single-byte chars). The default; recipient gets PayPal email link to claim.
- `PHONE` — unencrypted phone number.
- `PAYPAL_ID` — encrypted PayPal account number (the buyer's `payer_id` from past transactions).
- `USER_HANDLE` — Venmo username.

### `recipient_wallet`

Where the funds land:
- `PAYPAL` (default)
- `VENMO` — **US-only**, requires US mobile number, requires `note` field

```json
{
  "recipient_type": "USER_HANDLE",
  "recipient_wallet": "VENMO",
  "amount": { "value": "25.00", "currency": "USD" },
  "receiver": "@user-handle",
  "sender_item_id": "ref-12345",
  "note": "Bonus payout"
}
```

### Multi-item batch example

```json
{
  "sender_batch_header": {
    "sender_batch_id": "monthly_affiliate_2024_06",
    "email_subject": "Your monthly payout from ACME",
    "email_message": "Thanks for being an affiliate!"
  },
  "items": [
    {
      "recipient_type": "EMAIL",
      "receiver": "alice@example.com",
      "amount": { "value": "150.00", "currency": "USD" },
      "sender_item_id": "alice_2024_06"
    },
    {
      "recipient_type": "EMAIL",
      "receiver": "bob@example.com",
      "amount": { "value": "75.50", "currency": "USD" },
      "sender_item_id": "bob_2024_06"
    },
    {
      "recipient_type": "USER_HANDLE",
      "recipient_wallet": "VENMO",
      "receiver": "@charlie-handle",
      "amount": { "value": "45.00", "currency": "USD" },
      "note": "Payout from ACME for June",
      "sender_item_id": "charlie_2024_06"
    }
  ]
}
```

## Get batch details

```
GET /v1/payments/payouts/{payout_batch_id}
```

Query params: `page`, `page_size`, `fields`, `total_required` (default `false`). Returns `batch_header` (with `batch_status` and totals) and the `items` array.

## Get item details

```
GET /v1/payments/payouts-item/{payout_item_id}
```

Returns `payout_item_id`, `transaction_id`, `transaction_status`, `payout_item_fee`, and the original item.

## Cancel an unclaimed item

```
POST /v1/payments/payouts-item/{payout_item_id}/cancel
```

Empty body. Used to pull funds back before the 30-day auto-refund (e.g., wrong recipient). Item transitions to `RETURNED`.

## Status enums

**Batch status** (`batch_status`):
- `PENDING` — request received, processing soon
- `PROCESSING` — currently being processed
- `SUCCESS` — batch completed (all items either succeeded or final-failed)
- `DENIED` — batch rejected (often due to insufficient sender balance, fraud check)
- `CANCELED` — canceled via PayPal portal

**Item transaction status** (`transaction_status`):
- `SUCCESS` — funds delivered
- `FAILED` — failure (see error codes)
- `PENDING` — in flight
- `UNCLAIMED` — recipient hasn't claimed yet
- `RETURNED` — auto-refunded after 30 days unclaimed, or manually canceled
- `ONHOLD` — held for review
- `BLOCKED` — recipient blocked
- `REFUNDED` — refund issued
- `REVERSED` — reversed (admin action / fraud)

## Webhooks

Batch:
- `PAYMENT.PAYOUTSBATCH.PROCESSING`
- `PAYMENT.PAYOUTSBATCH.SUCCESS`
- `PAYMENT.PAYOUTSBATCH.DENIED`

Item:
- `PAYMENT.PAYOUTS-ITEM.SUCCEEDED` ← happy path
- `PAYMENT.PAYOUTS-ITEM.FAILED`
- `PAYMENT.PAYOUTS-ITEM.HELD`
- `PAYMENT.PAYOUTS-ITEM.BLOCKED`
- `PAYMENT.PAYOUTS-ITEM.CANCELED`
- `PAYMENT.PAYOUTS-ITEM.REFUNDED`
- `PAYMENT.PAYOUTS-ITEM.RETURNED`
- `PAYMENT.PAYOUTS-ITEM.UNCLAIMED`

Subscribe to per-item events; the batch events are summary signals. Both `SUCCEEDED` and `UNCLAIMED` should trigger updates in your system (e.g., notify the recipient on `UNCLAIMED` to remind them to claim).

## Country / currency notes

- 96 countries/regions, 20+ currencies.
- Sender accounts in **India** and **Mexico** are receive-only — they cannot send payouts.
- **Brazil (BRL):** non-Brazilian recipients require currency conversion; PayPal applies a spread.
- **Malaysia (MYR):** users cannot send payments requiring MYR-to-foreign conversion.
- **Venmo is US-only.**

Full country/feature reference: https://developer.paypal.com/docs/payouts/standard/reference/country-feature/

## Negative testing

Trigger specific failures by setting `items[0].note` to a magic string. Example: `note = "ERRPYO002"` triggers `SENDER_EMAIL_UNCONFIRMED`. Full per-API trigger table: https://developer.paypal.com/tools/sandbox/negative-testing/test-values/. See `sandbox-testing.md`.

## Common pitfalls

- **Mixed currencies in one batch** — fails immediately. Group by currency.
- **Exceeding 15,000 items** — split into multiple batches.
- **Reusing `sender_batch_id`** — second submission rejected for 30 days. Generate a new one each batch.
- **Insufficient sender balance** — batch goes `DENIED`. Either pre-fund the PayPal account or use linked bank with sufficient balance.
- **Recipient never claims** — funds auto-return after 30 days. Use `PAYMENT.PAYOUTS-ITEM.UNCLAIMED` to nudge them.
- **Trying to send to a recipient in a country that can only receive** — the item fails. Check `recipient_type`/country mapping for edge cases.

## Reference URLs

- Payouts overview: https://developer.paypal.com/docs/payouts/
- Standard Payouts integration: https://developer.paypal.com/docs/payouts/standard/integrate-api/
- Payouts API reference: https://developer.paypal.com/docs/api/payments.payouts-batch/v1/
- Country/currency feature: https://developer.paypal.com/docs/payouts/standard/reference/country-feature/
- Webhook event names: https://developer.paypal.com/api/rest/webhooks/event-names/
