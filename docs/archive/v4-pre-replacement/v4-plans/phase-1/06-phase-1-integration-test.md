# Phase 1.6 — Phase 1 end-to-end sanity test

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§5 Verification Strategy](../../PRD.md#5-verification-strategy), [§4 Phase 1](../../PRD.md#phased-rollout)

## Context

Phase 1 ships 5 plan files of REST plumbing (config, exceptions, HTTP, auth, base classes + facade, support utilities). Before Phase 2 starts adding resource handlers on top of this plumbing, we need a single end-to-end sanity test that drives a full request lifecycle through every Phase 1 component using a mock Transport. This catches integration bugs that the per-component unit tests miss (e.g., does the Authenticator actually inject its token via BaseResource's header pipeline? Does an exception thrown by Transport propagate through to the merchant call site with the right type?).

## Scope

- Build a single integration-style test that:
  1. Constructs `Config` with fake credentials and a mock Transport.
  2. Constructs `Client($config)`.
  3. Configures the mock Transport to respond to `POST /v1/oauth2/token` with a valid token response and to a fake resource POST with a 200 + sample body.
  4. Calls a resource method (e.g., a stub resource extended from BaseResource) and asserts the resulting DTO has the expected `id`, `debug_id`, etc.
  5. Resets the mock Transport, configures it to respond with 401, calls the same resource method, asserts `AuthenticationException` is thrown with the right debug_id.
  6. Resets, configures with 429 + `Retry-After: 30`, asserts `RateLimitException::retryAfter() === 30`.
- Smoke-verify the secret-redacting logger doesn't leak the client_secret when Authenticator runs.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `tests/Integration/REST/Phase1SanityTest.php` | NEW | End-to-end happy path + 401 + 429 cases |
| `tests/Fixtures/responses/oauth-token.json` | NEW | Captured token response |
| `tests/Fixtures/responses/sample-resource-200.json` | NEW | Captured 200 |
| `tests/Fixtures/responses/sample-resource-401.json` | NEW | Captured 401 with debug_id |
| `tests/Fixtures/responses/sample-resource-429.json` | NEW | Captured 429 with retry-after |

## Acceptance criteria

- [ ] The integration test passes on a clean checkout with no real PayPal credentials (uses only mocks).
- [ ] Happy path: 200 response → DTO with correct `id` + `debugId()`.
- [ ] 401 → `AuthenticationException` with the captured debug_id.
- [ ] 429 → `RateLimitException::retryAfter() === 30`.
- [ ] Logger output during the test does NOT contain the literal client_secret.
- [ ] `PayPal-Partner-Attribution-Id: WekoodoLLC_Ecom` is present in the captured outbound request.
- [ ] PHPStan level 5 clean.
- [ ] CI runs this test as part of the Unit suite (it doesn't need sandbox credentials).

## Verification

```bash
composer test -- --filter Phase1SanityTest
```

## References

- PRD: [§5 Verification Strategy / Unit Tests](../../PRD.md#5-verification-strategy)
- Upstream: every other Phase 1 file
- Downstream: Phase 2 (gated on Phase 1 sanity passing)
