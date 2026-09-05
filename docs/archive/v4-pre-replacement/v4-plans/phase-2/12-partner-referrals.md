# Phase 2.12 — Resources\PartnerReferrals

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/PartnerReferrals](../../PRD.md#proposed-file-structure)

## Context

Partner Referrals v2 (`/v2/customer/partner-referrals`) — generate signup links so merchants can onboard sub-merchants under a partner relationship. Currently scoped narrowly for v4.0 (no multi-party orchestration beyond what `AuthAssertionBuilder` already gives). The merchant just needs to call `createReferral()` to get an onboarding URL and `showReferral()` to check status.

## Scope

- `Resources\PartnerReferrals` exposing:
  - `createReferral(array $body, ?RequestOptions $opts = null): PartnerReferralResponse`
  - `showReferral(string $referralId): PartnerReferralResponse`
- `Responses\PartnerReferralResponse` — typed DTO with `id()`, `links()` (the onboarding URL is in `links` rel `action_url`).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/PartnerReferrals.php` | NEW | |
| `src/REST/Responses/PartnerReferralResponse.php` | NEW | |
| `tests/Unit/REST/Resources/PartnerReferralsTest.php` | NEW | |
| `tests/Integration/REST/PartnerReferralsHappyPathTest.php` | NEW | Sandbox-gated; requires partner program access |
| `tests/Fixtures/responses/partner-referrals-*.json` | NEW | |
| `documentation/rest/partner-referrals.md` | NEW | |

## Acceptance criteria

- [ ] Both methods work against mocked + sandbox responses.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/partner-referrals/v2/
