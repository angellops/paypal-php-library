# Phase 2.5 — Resources\Subscriptions

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Subscriptions](../../PRD.md#proposed-file-structure)

## Context

Recurring billing via `/v1/billing/subscriptions`. A subscription belongs to a plan (Phase 2.6) which belongs to a catalog product (Phase 2.7). The trio replaces Classic's `CreateRecurringPaymentsProfile`. The Classic-to-REST mapper (Phase 3.7) orchestrates Plans + Subscriptions creation from a single Classic call, so this resource needs a clean, complete API.

## Scope

- `Resources\Subscriptions` exposing:
  - `create(array $body, ?RequestOptions $opts = null): SubscriptionResponse`
  - `show(string $subscriptionId): SubscriptionResponse`
  - `update(string $subscriptionId, array $patches): void`
  - `cancel(string $subscriptionId, string $reason): void`
  - `suspend(string $subscriptionId, string $reason): void`
  - `activate(string $subscriptionId, string $reason): void`
  - `capture(string $subscriptionId, array $body, ?RequestOptions $opts = null): CaptureResponse`
  - `listTransactions(string $subscriptionId, ?DateTimeInterface $startTime = null, ?DateTimeInterface $endTime = null): array`
- `Responses\SubscriptionResponse` — typed DTO with `id()`, `status()`, `planId()`, `subscriber()`, etc.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Subscriptions.php` | NEW | |
| `src/REST/Responses/SubscriptionResponse.php` | NEW | |
| `tests/Unit/REST/Resources/SubscriptionsTest.php` | NEW | |
| `tests/Integration/REST/SubscriptionsHappyPathTest.php` | NEW | Sandbox-gated; depends on Plans + CatalogProducts being available |
| `tests/Fixtures/responses/subscriptions-*.json` | NEW | |
| `documentation/rest/subscriptions.md` | NEW | |

## Acceptance criteria

- [ ] All 8 methods work against mocked + sandbox responses.
- [ ] Sandbox integration test creates a subscription against a test plan, suspends, activates, cancels — full lifecycle green.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'REST\\Resources\\Subscriptions'
```

## References

- PayPal docs: https://developer.paypal.com/docs/api/subscriptions/v1/
