# Phase 2.14 — Resources\Reports

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Reports](../../PRD.md#proposed-file-structure)

## Context

Reporting v1 (`/v1/reporting/*`) — transaction search and balances. Replaces Classic's `TransactionSearch` and `GetBalance`. Search has aggressive rate limits (PayPal documents 30 calls / 30 days at one point — recent state may differ); this resource must surface 429s clearly.

## Scope

- `Resources\Reports` exposing:
  - `listTransactions(array $filters): TransactionSearchResponse` — accepts `start_date`, `end_date`, `transaction_id`, `transaction_type`, etc.
  - `listBalances(?\DateTimeInterface $asOfTime = null, ?string $currencyCode = null): BalanceResponse`
- `Responses\TransactionSearchResponse`, `BalanceResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Reports.php` | NEW | |
| `src/REST/Responses/TransactionSearchResponse.php` | NEW | |
| `src/REST/Responses/BalanceResponse.php` | NEW | |
| `tests/Unit/REST/Resources/ReportsTest.php` | NEW | Includes 429 handling test |
| `tests/Integration/REST/ReportsHappyPathTest.php` | NEW | Sandbox-gated; rate-limited |
| `tests/Fixtures/responses/reports-*.json` | NEW | |
| `documentation/rest/reports.md` | NEW | Document rate limits prominently |

## Acceptance criteria

- [ ] Both methods work against mocked + sandbox responses.
- [ ] 429 response → `RateLimitException::retryAfter()` returns the correct seconds.
- [ ] Doc page documents PayPal's documented rate limits for this API.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/transaction-search/v1/
