# Phase 3.7 — Recurring Payments mappers

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/ recurring entries](../../PRD.md#proposed-file-structure)

## Context

The most architecturally complex mapper group. Classic recurring profiles are a single entity (`CreateRecurringPaymentsProfile` does everything). REST splits this into three: Catalog Product → Plan → Subscription. The Create mapper orchestrates the trio: it creates a Catalog Product if one isn't already cached for the merchant, creates a Plan with the requested pricing schedule, creates a Subscription against the Plan, and returns a NVP-shaped response with a synthetic `PROFILEID` that maps to the Subscription id. Lookup mappers use the synthetic-id-to-Subscription-id mapping (similar pattern to EcTokenBridge).

## Scope

- `Legacy\Mappers\CreateRecurringPaymentsProfileMapper` — orchestrates CatalogProducts + Plans + Subscriptions. Caches one product per merchant (via TokenStore) to avoid recreating on every call. Returns NVP-shaped `PROFILEID` = synthetic `I-XXXXXXXX` token mapped to the REST subscription_id.
- `Legacy\Mappers\GetRecurringPaymentsProfileDetailsMapper` — `I-XXX → subscription_id` lookup, `GET /v1/billing/subscriptions/{id}`, NVP-shape the response.
- `Legacy\Mappers\ManageRecurringPaymentsProfileStatusMapper` — Suspend / Reactivate / Cancel actions → REST subscription suspend/activate/cancel.
- `Legacy\Mappers\UpdateRecurringPaymentsProfileMapper` — `PATCH /v1/billing/subscriptions/{id}`.
- `Legacy\Mappers\BillOutstandingAmountMapper` — REST `POST /v1/billing/subscriptions/{id}/capture`.
- `Legacy\Mappers\GetRecurringPaymentsProfileStatusMapper` — minimal status accessor backed by the same Show endpoint.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/CreateRecurringPaymentsProfileMapper.php` | NEW | Orchestrator |
| `src/Legacy/Mappers/GetRecurringPaymentsProfileDetailsMapper.php` | NEW | |
| `src/Legacy/Mappers/ManageRecurringPaymentsProfileStatusMapper.php` | NEW | Suspend / Reactivate / Cancel |
| `src/Legacy/Mappers/UpdateRecurringPaymentsProfileMapper.php` | NEW | |
| `src/Legacy/Mappers/BillOutstandingAmountMapper.php` | NEW | |
| `src/Legacy/Mappers/GetRecurringPaymentsProfileStatusMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/CreateRecurringPaymentsProfileMapperTest.php` | NEW | Mocks 3 sequential REST calls |
| `tests/Unit/Legacy/Mappers/GetRecurringPaymentsProfileDetailsMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/ManageRecurringPaymentsProfileStatusMapperTest.php` | NEW | All 3 status changes |
| `tests/Unit/Legacy/Mappers/UpdateRecurringPaymentsProfileMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/BillOutstandingAmountMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/GetRecurringPaymentsProfileStatusMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] `CreateRecurringPaymentsProfileMapper` orchestrates 3 REST calls and returns NVP with `PROFILEID` matching `/^I-[A-Z0-9]{17}$/`.
- [ ] Catalog product caching: a second call from the same merchant within the cache TTL doesn't create another product.
- [ ] Status mapper handles all 3 actions (Cancel, Suspend, Reactivate) with the correct REST endpoints.
- [ ] Round-trip tests pass for all 6 mappers.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: Phase 2 CatalogProducts + Plans + Subscriptions + TokenStore
