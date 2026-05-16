# Invoicing

Send a polished invoice email; recipient pays via PayPal. Useful for B2B, freelancer billing, and any case where the customer needs an invoice document rather than a checkout button.

Docs: https://developer.paypal.com/docs/invoicing/

## Lifecycle

- Create as **DRAFT** → optionally update → **SEND** → recipient gets email.
- Recipient pays → status becomes **PAID** (or **PARTIALLY_PAID**).
- You can mark as paid manually (off-PayPal payment), refund, or cancel.

Statuses (verified from v2 schema): `DRAFT`, `SENT`, `SCHEDULED`, `PAYMENT_PENDING`, `PAID`, `MARKED_AS_PAID`, `CANCELLED`, `REFUNDED`, `PARTIALLY_PAID`, `MARKED_AS_REFUNDED`, `UNPAID`, `PARTIALLY_REFUNDED`.

The integration guide notes `SENT` and `UNPAID` are both valid post-send states. Read from the API rather than computing.

## Create draft invoice

```
POST /v2/invoicing/invoices
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
```

Minimal body:
```json
{
  "detail": {
    "currency_code": "USD",
    "note": "Thanks for your business"
  },
  "invoicer": {
    "name": { "given_name": "Jane", "surname": "Doe" }
  },
  "primary_recipients": [{
    "billing_info": { "email_address": "customer@example.com" }
  }],
  "items": [{
    "name": "Website design",
    "quantity": "1",
    "unit_amount": { "currency_code": "USD", "value": "500.00" }
  }]
}
```

Returns `201 Created` with the invoice `id` (form `INV2-XXXX-XXXX-XXXX-XXXX`) and `status: "DRAFT"`.

Fuller invoice with tax, discount, terms, and reference number:
```json
{
  "detail": {
    "invoice_number": "INV-2024-0042",
    "reference": "PO-12345",
    "invoice_date": "2024-06-01",
    "currency_code": "USD",
    "note": "Thanks for your business",
    "terms_and_conditions": "Net 30; 1.5% per month after due date",
    "memo": "Internal memo not shown to recipient",
    "payment_term": { "term_type": "NET_30", "due_date": "2024-07-01" }
  },
  "invoicer": {
    "name": { "given_name": "Jane", "surname": "Doe" },
    "email_address": "merchant@example.com",
    "phones": [{ "country_code": "1", "national_number": "4081234567", "phone_type": "MOBILE" }]
  },
  "primary_recipients": [{
    "billing_info": {
      "name": { "given_name": "Acme", "surname": "Corp" },
      "email_address": "ap@acme.example.com",
      "address": {
        "address_line_1": "1 Acme Way",
        "admin_area_2": "New York",
        "admin_area_1": "NY",
        "postal_code": "10001",
        "country_code": "US"
      }
    }
  }],
  "items": [{
    "name": "Website design",
    "description": "Homepage redesign + 3 internal pages",
    "quantity": "1",
    "unit_amount": { "currency_code": "USD", "value": "500.00" },
    "tax": { "name": "Sales Tax", "percent": "8.875" }
  }, {
    "name": "Hosting setup",
    "quantity": "1",
    "unit_amount": { "currency_code": "USD", "value": "100.00" }
  }],
  "amount": {
    "breakdown": {
      "discount": { "invoice_discount": { "percent": "10", "amount_invoice_level": true } },
      "shipping": { "amount": { "currency_code": "USD", "value": "0.00" } }
    }
  },
  "configuration": {
    "partial_payment": { "allow_partial_payment": true, "minimum_amount_due": { "currency_code": "USD", "value": "100.00" } },
    "allow_tip": false,
    "tax_calculated_after_discount": true,
    "tax_inclusive": false
  }
}
```

## Send the invoice

```
POST /v2/invoicing/invoices/{invoice_id}/send
```

Body:
```json
{
  "send_to_invoicer": true,
  "send_to_recipient": true
}
```

Optional: `subject`, `note`, `additional_recipients` (max 100 emails). Note from PayPal docs: user-supplied `subject` and `note` may not be honored — the system applies a default. Confirm in your test send.

Response (verbatim from docs) returns the public payer-view URL:
```json
{
  "href": "https://api-m.paypal.com/invoice/p#INV2-Z56S-5LLA-Q52L-CPZ5",
  "rel": "payer-view",
  "method": "GET"
}
```

The `payer-view` link is what the recipient gets in the email (the `/p#...` is a PayPal-hosted page where they pay). You can also share that URL out-of-band.

## Generate an invoice number

```
POST /v2/invoicing/generate-next-invoice-number
```

Returns the next sequential invoice number from your account's series. Use it before creating an invoice if you want PayPal to manage the numbering (otherwise set `detail.invoice_number` yourself — must be unique per account).

The path renders inconsistently across PayPal's own pages. If `/v2/invoicing/generate-next-invoice-number` returns 404, also try `/v2/invoicing/invoices/next-invoice-number`. Check the live API console.

## Other endpoints

```
GET    /v2/invoicing/invoices                            (list, with pagination + filters)
GET    /v2/invoicing/invoices/{id}
PUT    /v2/invoicing/invoices/{id}                       (full update — replaces the invoice)
DELETE /v2/invoicing/invoices/{id}                       (DRAFT only)
POST   /v2/invoicing/invoices/{id}/remind                (send a reminder email)
POST   /v2/invoicing/invoices/{id}/cancel                (cancel a sent invoice)
POST   /v2/invoicing/invoices/{id}/payments              (record a payment received outside PayPal)
DELETE /v2/invoicing/invoices/{id}/payments/{tx_id}      (delete an externally-recorded payment)
POST   /v2/invoicing/invoices/{id}/refunds               (record a refund)
DELETE /v2/invoicing/invoices/{id}/refunds/{tx_id}
POST   /v2/invoicing/invoices/{id}/generate-qr-code      (returns a PNG QR code linking to payer-view)
POST   /v2/invoicing/search-invoices                     (filter search)
```

QR code: useful for printed invoices / in-person payment. Body parameters (`width`, `height`, `action`) are documented sparsely — common values: `width`/`height` 200-500 px, `action` of `pay` or `details`. If the live API rejects yours, fetch https://developer.paypal.com/docs/api/invoicing/v2/#invoices_generate-qr-code for the current schema.

Search:
```
POST /v2/invoicing/search-invoices?page=1&page_size=20&total_required=true
```
Filter body (paraphrased; verify field names against live docs):
```json
{
  "recipient_email": "customer@example.com",
  "status": ["SENT", "PAID"],
  "invoice_date_range": { "start": "2024-01-01", "end": "2024-12-31" }
}
```

## Templates

PayPal supports invoice templates so you don't have to repeat boilerplate. CRUD under `/v2/invoicing/templates`. Useful for repeat customers / standardized line items.

## Webhooks

Subscribe to:
- `INVOICING.INVOICE.CREATED`
- `INVOICING.INVOICE.UPDATED`
- `INVOICING.INVOICE.SCHEDULED`
- `INVOICING.INVOICE.PAID`           ← the success event most apps care about
- `INVOICING.INVOICE.CANCELLED`
- `INVOICING.INVOICE.REFUNDED`

The `PAID` event resource includes the invoice ID, payment amount, payment method, and transaction ID — use it to mark the order fulfilled in your system.

## Common pitfalls

- **Drafts can be deleted; sent invoices can only be cancelled.** Don't try to `DELETE` a `SENT` invoice — use `/cancel`.
- **Invoice numbers must be unique per account.** If you set your own `detail.invoice_number`, ensure idempotency in your code.
- **Currency must match across items and the breakdown.** Mixed currencies error.
- **`SENT` and `UNPAID` are both post-send states.** Handle both in your status logic.
- **Recipients pay via PayPal's hosted page** — you don't need a JS SDK or your own checkout flow. The link in the email is the entire payment UX.
- **Subject/note overrides may be ignored.** Don't rely on them for critical info; put critical info in `detail.note` or `terms_and_conditions`.

## Reference URLs

- Invoicing overview: https://developer.paypal.com/docs/invoicing/
- Integration: https://developer.paypal.com/docs/invoicing/integrate/
- Invoicing v2 API: https://developer.paypal.com/docs/api/invoicing/v2/
