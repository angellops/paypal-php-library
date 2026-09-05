# Phase 2.13 — Resources\Identity

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Identity](../../PRD.md#proposed-file-structure), [§2 Non-Goals](../../PRD.md#non-goals-v40)

## Context

Identity / OpenID Connect (`/v1/identity/openidconnect/userinfo`) — return profile info for the OAuth-authenticated user. **Important scope limit:** v4.0 ships only the `client_credentials` flow. The 3-legged "Log in with PayPal" user flow is explicitly out of scope (Non-Goal #2). This resource just exposes the userinfo endpoint so a merchant with a user-flow access token (acquired out-of-band) can still call it.

## Scope

- `Resources\Identity` exposing:
  - `userInfo(string $accessToken): IdentityResponse` — accepts a caller-provided access token (NOT the client_credentials token) since v4.0 doesn't orchestrate the 3-legged flow.
- `Responses\IdentityResponse` — typed DTO with `subject()`, `email()`, `verifiedEmail()`, `payerId()`, etc.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Identity.php` | NEW | |
| `src/REST/Responses/IdentityResponse.php` | NEW | |
| `tests/Unit/REST/Resources/IdentityTest.php` | NEW | |
| `tests/Integration/REST/IdentityHappyPathTest.php` | NEW | Sandbox-gated; skipped if no user-flow access token available |
| `tests/Fixtures/responses/identity-userinfo.json` | NEW | |
| `documentation/rest/identity.md` | NEW | Documents the 3-legged-flow out-of-scope-ness clearly |

## Acceptance criteria

- [ ] `userInfo()` works against a mocked response.
- [ ] Doc page explicitly states that v4.0 does not orchestrate the user-flow OAuth handshake.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/identity/v1/
- PRD: [§2 Non-Goals #2](../../PRD.md#non-goals-v40)
