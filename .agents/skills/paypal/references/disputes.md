# Disputes

Buyer-initiated complaints. The Customer Disputes API lets you list open cases, fetch details, send messages, submit evidence, accept claims, and make settlement offers programmatically.

Docs: https://developer.paypal.com/docs/disputes/
API ref: https://developer.paypal.com/docs/api/customer-disputes/v1/

## Lifecycle

**`dispute_life_cycle_stage`** — the high-level phase:
- `INQUIRY` — initial phase; buyer and seller can communicate, make offers
- `CHARGEBACK` — escalated; evidence submission required
- `PRE_ARBITRATION`
- `ARBITRATION`

**`status`** — confirmed values from API ref:
- `OPEN`
- `UNDER_REVIEW`
- `WAITING_FOR_BUYER_RESPONSE`
- `WAITING_FOR_SELLER_RESPONSE`
- `RESOLVED`

**`dispute_outcome.outcome_code`** — the outcome when resolved. Verbatim-confirmed: `RESOLVED_BUYER_FAVOUR` (note British spelling). Other values like `RESOLVED_SELLER_FAVOR` likely exist but couldn't be enumerated from the docs — read from the API.

The `accept-claim` and `make-offer` endpoints accept their own offer_type / accept_claim_type enums, distinct from outcome_code.

## Reason codes

From the API reference (the structurally-confirmed values):
- `MERCHANDISE_OR_SERVICE_NOT_RECEIVED`
- `MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED`

The integration guide mentions additional values used in inquiries (verbatim spellings):
- `UNAUTHORISED` (British spelling — match exactly)
- `CREDIT_NOT_PROCESSED`
- `DUPLICATE_TRANSACTION`
- `INCORRECT_AMOUNT`
- `PAYMENT_BY_OTHER_MEANS`
- `CANCELED_RECURRING_BILLING`
- `OTHER`

Spelling varies (`UNAUTHORISED` vs `UNAUTHORIZED`). Always read from the API response — don't hardcode.

## List disputes

```
GET /v1/customer/disputes
Authorization: Bearer <ACCESS_TOKEN>
```

Query params:
- `disputed_transaction_id` — filter by transaction
- `page` (1-50, default 1), `page_size` (1-50, default 10)
- `dispute_state` — comma-separated states
- `create_time_before`, `create_time_after`, `update_time_before`, `update_time_after` — RFC 3339, must be within last 180 days
- `start_time` and `next_page_token` are **deprecated** — use the time-range filters

Sample (verbatim from docs):
```bash
curl -X GET 'https://api-m.sandbox.paypal.com/v1/customer/disputes?seller_protection_types=SELLER_PROTECTION_INELIGIBLE' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer <ACCESS_TOKEN>'
```

## Get a dispute

```
GET /v1/customer/disputes/{id}
```

Sample response (verbatim):
```json
{
  "dispute_id": "PP-D-4012",
  "create_time": "2024-04-11T04:18:00.000Z",
  "update_time": "2024-04-21T04:19:08.000Z",
  "reason": "MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED",
  "status": "RESOLVED",
  "dispute_life_cycle_stage": "CHARGEBACK",
  "dispute_channel": "INTERNAL",
  "dispute_outcome": { "outcome_code": "RESOLVED_BUYER_FAVOUR" },
  "links": [
    { "href": "https://api-m.sandbox.paypal.com/v1/customer/disputes/PP-D-4012",
      "rel": "self", "method": "GET" }
  ]
}
```

The full object also has `disputed_transactions[]`, `dispute_amount`, `messages[]` (the back-and-forth), `extensions`, `offer`, `seller_response_due_date`, `buyer_response_due_date`.

**Always read `seller_response_due_date` from the response** — don't try to compute deadlines yourself.

## Provide evidence

```
POST /v1/customer/disputes/{id}/provide-evidence
```

Multipart upload (`multipart/related`, `multipart/form-data`, or `multipart/mixed`). Body has an `evidences` array (0-100 items) plus optional `return_shipping_address` / `return_shipping_address_info`.

`evidence_type` enum:
- `PROOF_OF_DELIVERY`
- `PROOF_OF_REFUND`
- `PROOF_OF_FULFILLMENT`
- `PROOF_OF_DAMAGE`
- `THIRDPARTY_PROOF_FOR_DAMAGE_OR_SIGNIFICANT_DIFFERENCE`
- `DECLARATION`
- `PROOF_OF_MISSING_ITEMS`
- `PROOF_OF_EMPTY_PACKAGE_OR_DIFFERENT_ITEM`
- `PROOF_OF_ITEM_NOT_RECEIVED`

File limits: total 50 MB per case, individual files under 10 MB. Supported formats: JPG, GIF, PNG, PDF. Notes max 2000 chars.

Example metadata payload (the JSON part of the multipart):
```json
{
  "evidences": [{
    "evidence_type": "PROOF_OF_FULFILLMENT",
    "evidence_info": {
      "tracking_info": [{
        "carrier_name": "FEDEX",
        "tracking_number": "123456789012"
      }]
    },
    "notes": "Package delivered to buyer per tracking; signature obtained"
  }]
}
```

The full multipart construction varies by language. Fetch https://developer.paypal.com/docs/api/customer-disputes/v1/#disputes_provide-evidence for the canonical curl example.

## Other actions

- **Accept the claim** (full refund the buyer):
  ```
  POST /v1/customer/disputes/{id}/accept-claim
  ```
  ```json
  { "note": "Accepting refund", "accept_claim_type": "REFUND" }
  ```
  `accept_claim_type` enum: `REFUND`, `REFUND_WITH_RETURN`, `PARTIAL_REFUND`, `REFUND_WITH_RETURN_SHIPMENT_LABEL`.

- **Send a message** to the buyer:
  ```
  POST /v1/customer/disputes/{id}/send-message
  ```
  ```json
  { "message": "We've reviewed your concern and..." }
  ```

- **Make a settlement offer**:
  ```
  POST /v1/customer/disputes/{id}/make-offer
  ```
  ```json
  {
    "note": "We'd like to offer a partial refund",
    "offer_type": "REFUND",
    "offer_amount": { "currency_code": "USD", "value": "25.00" }
  }
  ```
  `offer_type` enum: `REFUND`, `REFUND_WITH_RETURN`, `REFUND_WITH_REPLACEMENT`, `REPLACEMENT_WITHOUT_REFUND`. Counterparts: `accept-offer`, `deny-offer`.

- **Escalate to claim**: `POST /v1/customer/disputes/{id}/escalate`
- **Appeal** (after resolution): `POST /v1/customer/disputes/{id}/appeal`
- **Sandbox: force outcome**: `POST /v1/customer/disputes/{id}/adjudicate` with `outcome` `BUYER_FAVOR` or `SELLER_FAVOR`
- **Sandbox: change status**: `POST /v1/customer/disputes/{id}/require-evidence`
- **Acknowledge return**: `POST /v1/customer/disputes/{id}/acknowledge-return-item`
- **Provide supporting info**: `POST /v1/customer/disputes/{id}/provide-supporting-info`
- **Patch (limited)**: `PATCH /v1/customer/disputes/{id}`

## Webhooks

- `CUSTOMER.DISPUTE.CREATED` ← new dispute opened against you
- `CUSTOMER.DISPUTE.UPDATED` ← buyer responded, status changed, etc.
- `CUSTOMER.DISPUTE.RESOLVED` ← final outcome reached
- `RISK.DISPUTE.CREATED` — superseded by `CUSTOMER.DISPUTE.CREATED`; ignore for new code

Common pattern: on `CUSTOMER.DISPUTE.CREATED`, ping your operations team with the `dispute_id`, `dispute_life_cycle_stage`, `reason`, and `seller_response_due_date`. On `CUSTOMER.DISPUTE.UPDATED`, re-fetch the dispute to surface any new buyer messages.

## Auto-escalation

The integration guide notes: "If the customer and merchant cannot resolve the dispute within the 20-day inquiry period, the customer or merchant can escalate the dispute to PayPal." But the per-stage deadlines are **always** in the dispute object's `seller_response_due_date` and `buyer_response_due_date`. **Use those, not hardcoded day counts** — PayPal can change defaults and apply per-region rules.

## Common pitfalls

- **Spelling: `RESOLVED_BUYER_FAVOUR`, `UNAUTHORISED`** — these are British. Match exactly when filtering.
- **Date filter window: 180 days max.** Looking older requires changing the time range.
- **`require-evidence` and `adjudicate` are sandbox-only.** Don't include them in production code paths.
- **Evidence files have hard limits.** 10 MB per file, 50 MB total. Compress photos, paginate PDFs.
- **`UNDER_REVIEW`** means PayPal is investigating — your `provide-evidence` window has closed. You can still send messages and check status, but new evidence won't be accepted.
- **Don't compute due dates.** PayPal does it server-side accounting for weekends/holidays/region. Read `seller_response_due_date`.

## Reference URLs

- Disputes overview: https://developer.paypal.com/docs/disputes/
- Integration guide: https://developer.paypal.com/docs/disputes/integration-guide/
- Disputes reference: https://developer.paypal.com/docs/disputes/disputes-reference/
- FAQ: https://developer.paypal.com/docs/disputes/faq/
- Customer Disputes v1 API: https://developer.paypal.com/docs/api/customer-disputes/v1/
