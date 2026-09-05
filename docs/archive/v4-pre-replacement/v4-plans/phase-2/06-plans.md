# Phase 2.6 — Resources\Plans

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Plans](../../PRD.md#proposed-file-structure)

## Context

Billing plans (`/v1/billing/plans`) define the price + frequency + cycles of a subscription. Plans depend on a Catalog Product. Classic's `CreateRecurringPaymentsProfile` mapper (Phase 3.7) creates a Plan on-the-fly before creating the Subscription, so the Plans resource needs a working `create()` that mappers can call.

## Scope

- `Resources\Plans` exposing:
  - `create(array $body, ?RequestOptions $opts = null): PlanResponse`
  - `list(?int $page = null, ?int $pageSize = null): array<PlanResponse>`
  - `show(string $planId): PlanResponse`
  - `update(string $planId, array $patches): void`
  - `activate(string $planId): void`
  - `deactivate(string $planId): void`
  - `updatePricing(string $planId, array $pricingSchemes): void`
- `Responses\PlanResponse` — typed DTO.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Plans.php` | NEW | |
| `src/REST/Responses/PlanResponse.php` | NEW | |
| `tests/Unit/REST/Resources/PlansTest.php` | NEW | |
| `tests/Integration/REST/PlansHappyPathTest.php` | NEW | Sandbox-gated; depends on a test CatalogProduct |
| `tests/Fixtures/responses/plans-*.json` | NEW | |
| `documentation/rest/plans.md` | NEW | |

## Acceptance criteria

- [ ] All 7 methods work against mocked + sandbox responses.
- [ ] Sandbox integration test creates a plan, activates, deactivates — leaves no orphan state.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/subscriptions/v1/#plans
