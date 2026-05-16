# Reports & Transaction Search

For reconciliation, accounting, fraud analysis, and revenue reporting. PayPal exposes data through three channels: the merchant dashboard (visual), SFTP-delivered files (bulk batch), and the Transaction Search REST API (programmatic).

Docs: https://developer.paypal.com/docs/reports/

## Channels at a glance

| Channel | Best for | Latency | Format |
|---|---|---|---|
| Dashboard | Ad-hoc viewing, manual reconciliation | Live | HTML / CSV download |
| SFTP reports | Batch ingestion to data warehouse | Daily (after 24h) | CSV / TSV |
| Transaction Search API | Programmatic queries, dashboards, bots | ~3 hours | JSON |

The `BALANCE` and `transaction-search` REST endpoints overlap with SFTP report content. Pick the integration that matches your latency and integration shape.

## SFTP reports

Files placed in `/ppreports/outgoing` on PayPal's SFTP server. Account owners provision SFTP usernames in their PayPal account settings. Files retained one year.

Verified report names:
1. **Case Report**
2. **Case Report ACM**
3. **Balance Report**
4. **Dispute Detail Custom** (access by request through PayPal account manager)
5. **Order Report**
6. **Preapproved Payments Agreement**
7. **Recurring Payments Profile**
8. **Revenue Share**
9. **Settlement** (the most-used — daily settled-transaction summary)
10. **Transaction Details**

**Settlement Report** specifics:
- Format: CSV (comma-separated) or tab-delimited; UTF-8.
- Frequency: daily, posted by 12:00 PM no later than 24h after the transaction date.
- File naming: `STL-yyyymmdd.sequenceNumber.version.format` (single-account).
- Contains: transaction IDs, invoice IDs, event codes, gross/net amounts, fees, currency, debit/credit indicator, beginning and ending balance.

For other reports, fetch their per-report doc page from https://developer.paypal.com/docs/reports/sftp-reports/.

## Transaction Search REST API

Endpoints:
- `GET /v1/reporting/transactions` — list transactions in a date range
- `GET /v1/reporting/balances` — list account balances
- `GET /v1/reporting/get-balance-net-summary`
- `GET /v1/reporting/get-daily-summary`

### Query transactions

```
GET /v1/reporting/transactions?start_date=2024-06-01T00:00:00-0700&end_date=2024-06-30T23:59:59-0700&fields=all&page_size=100
Authorization: Bearer <ACCESS_TOKEN>
```

Query params:
- `start_date` (required, RFC 3339), `end_date` (required, RFC 3339) — see constraints below
- `transaction_id` — filter to one transaction
- `transaction_type` — code (e.g., `T0006` for sales)
- `transaction_status`:
  - `D` — denied
  - `P` — pending
  - `S` — successful
  - `V` — reversed
- `transaction_amount` — range syntax `[500 TO 1005]` (cents — that's $5.00 to $10.05)
- `transaction_currency` — ISO 4217
- `payment_instrument_type` — `CREDITCARD` or `DEBITCARD`
- `store_id`, `terminal_id` — POS-specific
- `fields` — default `transaction_info`; pass `all` for `payer_info`, `shipping_info`, `auction_info`, `cart_info`, `incentive_info`, `store_info`
- `balance_affecting_records_only` — default `Y`
- `page_size` (1-500, default 100), `page` (default 1)

### Critical constraints (verbatim from docs)

- **Maximum supported range is 31 days.** Loop in 31-day chunks for longer windows.
- **It takes a maximum of three hours for executed transactions to appear.** Don't query "the last hour" expecting completeness.
- **This call lists transaction for the previous three years.** Older data needs SFTP/Settlement reports or PayPal Support.
- **Transaction IDs are not unique across the reporting system.** A `transaction_id` filter can return multiple records.
- **If you specify optional query params, the `ending_balance` response field is empty.** Run a separate balance query if needed.

### Response shape

Trimmed sample:
```json
{
  "transaction_details": [{
    "transaction_info": {
      "transaction_id": "5DR50018P5266213U",
      "transaction_initiation_date": "2024-06-15T14:32:11+0000",
      "transaction_updated_date": "2024-06-15T14:32:14+0000",
      "transaction_amount": { "currency_code": "USD", "value": "100.00" },
      "fee_amount": { "currency_code": "USD", "value": "-3.00" },
      "transaction_status": "S",
      "transaction_subject": "Order #1234",
      "ending_balance": { "currency_code": "USD", "value": "1245.50" }
    },
    "payer_info": { ... },
    "shipping_info": { ... },
    "cart_info": { ... }
  }],
  "page": 1,
  "total_items": 1,
  "total_pages": 1,
  "links": [...]
}
```

### Get balances

```
GET /v1/reporting/balances?currency_code=USD&as_of_time=2024-06-01T00:00:00Z
```

Returns current (or as-of) balance per currency held in the account.

## Reports in the dashboard

URL: https://www.paypal.com/businessmanage/reports

The dashboard surfaces:
- Transactions
- Balance
- Disputes
- Tax (1099-K for US merchants)
- Payouts (if you use Standard or Advanced Payouts)
- Settlement (downloadable PDF + CSV)

Reports update daily. The dashboard is also the unblock for some PayPal-internal reports that aren't exposed via REST.

## Integration patterns

**For daily reconciliation:**
- Schedule a job to pull yesterday's Settlement Report (SFTP) or call Transaction Search for `start_date=yesterday 00:00, end_date=yesterday 23:59:59`.
- Cross-reference each PayPal transaction with your internal order/payment records.
- Surface discrepancies (missing on PayPal side, missing on your side, amount mismatch).

**For real-time updates:**
- Use **webhooks** (`PAYMENT.CAPTURE.COMPLETED`, etc.) instead of polling Transaction Search. Webhooks deliver in seconds, Transaction Search lags up to 3 hours.

**For tax / 1099 / accounting:**
- Use SFTP Settlement reports — they include the breakdown of fees, currency conversion, and balance changes that accountants need.
- Or, for low volume, download monthly reports from the dashboard.

## Common pitfalls

- **Querying a >31-day range** → 400 error. Chunk it.
- **Querying recent transactions and missing some** → wait 3+ hours and re-query, or rely on webhooks.
- **`ending_balance` is empty when you filter** — separate `/balances` call if needed.
- **Transaction Search isn't real-time enough for fraud/ops dashboards.** Combine with webhooks.
- **SFTP file timing varies regionally** — set your job to retry if no file by 1pm.
- **Transaction Search for >3 years back** → use SFTP archive or PayPal Support.

## Reference URLs

- Reports overview: https://developer.paypal.com/docs/reports/
- Reports reference: https://developer.paypal.com/docs/reports/reference/
- SFTP reports index: https://developer.paypal.com/docs/reports/sftp-reports/
- Settlement Report spec: https://developer.paypal.com/docs/reports/sftp-reports/settlement-report/
- Transaction Search v1 API: https://developer.paypal.com/docs/api/transaction-search/v1/
