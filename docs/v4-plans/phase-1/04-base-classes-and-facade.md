# Phase 1.4 — Base classes + Client facade

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§3 REST/Client + Resources/BaseResource + Responses/BaseResponse](../../PRD.md#proposed-file-structure), [§4 Phase 1](../../PRD.md#phased-rollout)

## Context

Three structural classes tie the previous Phase 1 components together. `Responses\BaseResponse` is the parent of every typed DTO returned from resource handlers — it exposes `ArrayAccess` (so merchants migrating from associative-array Classic responses can still index into responses without rewriting call sites), `debugId()`, and a `toArray()` accessor. `Resources\BaseResource` takes a `Transport` + `OAuth2Authenticator` + `Config` and provides shared request-building helpers (URL composition, auth header injection, `PayPal-Partner-Attribution-Id` header injection). `REST\Client` is the merchant-facing facade: `new Client($config)->orders->create(...)` — lazy resource properties so unused resources don't get constructed.

## Scope

- `Responses\BaseResponse` — `ArrayAccess` + `JsonSerializable` + `debugId()` + `toArray()` + `raw()` (returns the underlying parsed JSON array).
- `Resources\BaseResource` — protected helpers: `get($path, $opts)`, `post($path, $body, $opts)`, `put($path, $body, $opts)`, `patch($path, $body, $opts)`, `delete($path, $opts)`. Each builds the right `Request`, calls `Transport::send()`, and returns a `Response`. Injects `Authorization: Bearer <token>` from `OAuth2Authenticator` and `PayPal-Partner-Attribution-Id: WekoodoLLC_Ecom` from `Support\PartnerAttribution::VALUE` on every request.
- `REST\Client` — facade with lazy resource accessors via magic `__get` or explicit properties. Constructor takes `Config`. Exposes `orders`, `payments`, `subscriptions`, `plans`, `catalogProducts`, `invoicing`, `payouts`, `disputes`, `webhooks`, `webhookVerifier`, `identity`, `vault`, `partnerReferrals`, `reports`. Each property is instantiated on first access using shared dependencies.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Responses/BaseResponse.php` | NEW | ArrayAccess + JsonSerializable + debugId() + toArray() |
| `src/REST/Resources/BaseResource.php` | NEW | get/post/put/patch/delete helpers |
| `src/REST/Client.php` | NEW | Facade with lazy resource properties |
| `tests/Unit/REST/Responses/BaseResponseTest.php` | NEW | ArrayAccess + debugId + iteration |
| `tests/Unit/REST/Resources/BaseResourceTest.php` | NEW | Header injection + URL composition |
| `tests/Unit/REST/ClientTest.php` | NEW | Lazy resource construction, dependency wiring |

## Acceptance criteria

- [ ] `BaseResponse` instances support `$response['id']`, `$response['status']`, etc. via `ArrayAccess`.
- [ ] `BaseResponse::debugId()` returns the `PAYPAL-DEBUG-ID` from the underlying HTTP response.
- [ ] `BaseResource::post()` builds a request with the expected URL, body, `Authorization: Bearer <token>`, `Content-Type: application/json`, `PayPal-Partner-Attribution-Id: WekoodoLLC_Ecom` headers.
- [ ] `Client::orders` returns an `Orders` instance only on first access (lazy); subsequent accesses return the same instance.
- [ ] `Client` exposes all 14 resource properties; accessing each works without errors against a mocked Transport.
- [ ] PHPStan level 5 clean.
- [ ] Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'REST\\(Responses|Resources|Client)'
composer phpstan -- --paths=src/REST/Responses/,src/REST/Resources/,src/REST/Client.php
```

## References

- PRD: [§2.1 AC2.1-AC2.3](../../PRD.md#user-stories--acceptance-criteria), [§3 REST/Client in file structure](../../PRD.md#proposed-file-structure)
- Upstream: [`01-config-and-exceptions.md`](01-config-and-exceptions.md), [`02-http-layer.md`](02-http-layer.md), [`03-auth-and-tokens.md`](03-auth-and-tokens.md)
- Downstream: all of Phase 2 (every resource extends BaseResource; every DTO extends BaseResponse)
