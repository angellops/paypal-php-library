# Phase 2.10 — Resources\Disputes

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Disputes](../../PRD.md#proposed-file-structure)

## Context

Customer disputes (`/v1/customer/disputes`) — list, show, accept claim, provide evidence, escalate, acknowledge return shipment, make offers. Sandbox-vs-live divergence is high here (sandbox dispute lifecycle timings differ from live), so integration tests need careful fixtures.

## Scope

- `Resources\Disputes` exposing:
  - `list(?array $filters = null): array<DisputeResponse>`
  - `show(string $disputeId): DisputeResponse`
  - `provideEvidence(string $disputeId, array $body): void`
  - `acceptClaim(string $disputeId, array $body): void`
  - `acknowledgeReturnItem(string $disputeId, array $body): void`
  - `escalate(string $disputeId, array $body): void`
  - `makeOffer(string $disputeId, array $body): void`
  - `acceptOffer(string $disputeId, array $body): void`
- `Responses\DisputeResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Disputes.php` | NEW | |
| `src/REST/Responses/DisputeResponse.php` | NEW | |
| `tests/Unit/REST/Resources/DisputesTest.php` | NEW | |
| `tests/Integration/REST/DisputesHappyPathTest.php` | NEW | Sandbox-gated; uses PayPal's "simulate dispute" capability in dev portal |
| `tests/Fixtures/responses/disputes-*.json` | NEW | |
| `documentation/rest/disputes.md` | NEW | Flag sandbox-vs-live divergence on dispute lifecycle |

## Acceptance criteria

- [ ] All 8 methods work against mocked + sandbox responses.
- [ ] Document at least 2 sandbox-vs-live caveats in the doc page.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/customer-disputes/v1/
