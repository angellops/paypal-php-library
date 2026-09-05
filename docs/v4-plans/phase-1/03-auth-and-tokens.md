# Phase 1.3 — Auth + TokenStore

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§3 REST/Auth/ and TokenStore/](../../PRD.md#proposed-file-structure), [§4 Phase 1](../../PRD.md#phased-rollout)

## Context

OAuth 2.0 client_credentials flow + token caching. `OAuth2Authenticator` is the single source of token freshness — it asks the configured `TokenStore` for a cached token, validates expiry, and fetches a new one via `/v1/oauth2/token` only when needed. The fetch logic uses jittered single-flight refresh to avoid thundering-herd on token expiry across concurrent FPM workers. Four `TokenStore` implementations ship: in-memory (default — token lives for one PHP request), filesystem (recommended for production — 0600 perms, refuses world-readable directories), PSR-16 cache adapter, and the abstract interface. `AuthAssertionBuilder` constructs `PayPal-Auth-Assertion` JWTs for multi-party scenarios (deferred use in v4.0 but shipped so partner-referrals work cleanly).

## Scope

- `Auth\AccessToken` — readonly value object: `token`, `expiresAt`, `appId`, `scopes[]`. `isExpired(int $skewSeconds = 30): bool` accessor.
- `Auth\OAuth2Authenticator` — `getAccessToken(): AccessToken`. Single-flight refresh: when token is expired or missing, picks a jittered delay (`random_int(0, 500)` ms), acquires the `TokenStore`'s lock if supported, double-checks freshness, fetches if still stale, stores, releases. Uses the injected `Transport` for the actual OAuth POST.
- `Auth\AuthAssertionBuilder` — `forPayerId(string $payerId): string`. Returns the JWT string suitable for the `PayPal-Auth-Assertion` header. Used by `PartnerReferrals` and multi-party scenarios.
- `TokenStore\TokenStoreInterface` — `get(string $key): ?AccessToken`, `put(string $key, AccessToken $token): void`, optional `lock(string $key): callable` returning an unlocker.
- `TokenStore\InMemoryTokenStore` — default. Process-local array.
- `TokenStore\FilesystemTokenStore` — atomic file-write with 0600 perms. Refuses to operate if the directory is world-readable. Uses `flock()` for lock support.
- `TokenStore\Psr16TokenStore` — adapter over any PSR-16 `CacheInterface`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Auth/AccessToken.php` | NEW | readonly VO |
| `src/REST/Auth/OAuth2Authenticator.php` | NEW | single-flight refresh with jitter |
| `src/REST/Auth/AuthAssertionBuilder.php` | NEW | JWT builder |
| `src/REST/TokenStore/TokenStoreInterface.php` | NEW | three methods |
| `src/REST/TokenStore/InMemoryTokenStore.php` | NEW | default |
| `src/REST/TokenStore/FilesystemTokenStore.php` | NEW | 0600, refuses world-readable |
| `src/REST/TokenStore/Psr16TokenStore.php` | NEW | PSR-16 adapter |
| `tests/Unit/REST/Auth/OAuth2AuthenticatorTest.php` | NEW | Mocked Transport; concurrency test forks 10 PHP processes and asserts exactly one OAuth POST fired |
| `tests/Unit/REST/Auth/AccessTokenTest.php` | NEW | expiry + skew |
| `tests/Unit/REST/Auth/AuthAssertionBuilderTest.php` | NEW | JWT shape |
| `tests/Unit/REST/TokenStore/FilesystemTokenStoreTest.php` | NEW | world-readable refusal, 0600 perms |
| `tests/Unit/REST/TokenStore/Psr16TokenStoreTest.php` | NEW | mock PSR-16 cache |

## Acceptance criteria

- [ ] `OAuth2Authenticator::getAccessToken()` returns a cached token when the cached one is fresh; never hits the network in that case.
- [ ] Concurrent `getAccessToken()` calls across 10 forked PHP processes result in exactly ONE OAuth POST (single-flight refresh works).
- [ ] `FilesystemTokenStore` throws if the directory is world-readable.
- [ ] `FilesystemTokenStore` writes files with 0600 perms.
- [ ] `Psr16TokenStore` round-trips an `AccessToken` through a mock PSR-16 `CacheInterface`.
- [ ] `AuthAssertionBuilder` produces a JWT that, when base64-decoded, contains the expected `iss` (client_id) and `payer_id` claims.
- [ ] PHPStan level 5 clean.
- [ ] Coverage ≥80% on Auth/ and TokenStore/.

## Verification

```bash
composer test -- --filter 'REST\\(Auth|TokenStore)'
composer phpstan -- --paths=src/REST/Auth/,src/REST/TokenStore/
```

## References

- PRD: [§3 Auth + TokenStore in file structure](../../PRD.md#proposed-file-structure), [§4 Risks / Token store concurrency](../../PRD.md#technical-risks)
- Upstream: [`01-config-and-exceptions.md`](01-config-and-exceptions.md), [`02-http-layer.md`](02-http-layer.md)
- Downstream: [`04-base-classes-and-facade.md`](04-base-classes-and-facade.md) (Client wires Authenticator into BaseResource), every Phase 2 resource (all auth via this layer)
