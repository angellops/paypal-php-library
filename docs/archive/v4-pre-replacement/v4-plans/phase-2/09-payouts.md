# Phase 2.9 — Resources\Payouts

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Payouts](../../PRD.md#proposed-file-structure)

## Context

Payouts v1 (`/v1/payments/payouts`) — bulk send money to multiple receivers. Replaces Classic's `MassPay`. PayPal's REST payouts have a different shape than MassPay (asynchronous batch, items can be queried individually, unclaimed payouts have their own endpoint), so the mapper in Phase 3.6 must handle behavioral differences carefully.

## Scope

- `Resources\Payouts` exposing:
  - `createBatch(array $body, ?RequestOptions $opts = null): PayoutBatchResponse`
  - `showBatch(string $batchId, ?int $page = null, ?int $pageSize = null): PayoutBatchResponse`
  - `showItem(string $itemId): PayoutItemResponse`
  - `cancelUnclaimedItem(string $itemId): PayoutItemResponse`
- `Responses\PayoutBatchResponse`, `PayoutItemResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Payouts.php` | NEW | |
| `src/REST/Responses/PayoutBatchResponse.php` | NEW | |
| `src/REST/Responses/PayoutItemResponse.php` | NEW | |
| `tests/Unit/REST/Resources/PayoutsTest.php` | NEW | |
| `tests/Integration/REST/PayoutsHappyPathTest.php` | NEW | Sandbox-gated; sends a small batch to a sandbox receiver |
| `tests/Fixtures/responses/payouts-*.json` | NEW | |
| `documentation/rest/payouts.md` | NEW | Document MassPay → Payouts behavioral differences |

## Acceptance criteria

- [ ] All 4 methods work against mocked + sandbox responses.
- [ ] Document at least 3 behavioral differences vs. Classic MassPay (async batch, item-level status, unclaimed handling) in the doc page.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/payments.payouts-batch/v1/
