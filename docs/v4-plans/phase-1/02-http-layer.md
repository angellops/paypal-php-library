# Phase 1.2 — Http layer (Transport + Request/Response + RequestOptions + Prefer)

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§3 REST/Http/](../../PRD.md#proposed-file-structure), [§4 Phase 1](../../PRD.md#phased-rollout)

## Context

Builds the HTTP abstraction layer that sits between resource handlers and `api-m.paypal.com`. Resources construct `Request` value objects, pass them to a `TransportInterface`, and receive `Response` value objects back. The default transport is a zero-dep `CurlTransport`; a `GuzzleTransport` is offered as a `suggest` alternative (not built in this plan — only the interface). Per-call `RequestOptions` carries the idempotency key (`PayPal-Request-Id`), `Prefer` header value, optional auth assertion, and custom headers.

## Scope

- `Http\TransportInterface` — single method `send(Request $request): Response`. Throws `TransportException` on cURL/network errors; throws `PayPalApiException` subclasses on HTTP error statuses.
- `Http\CurlTransport` — default impl. SSL verify ON by default (`CURLOPT_SSL_VERIFYPEER => true`, `CURLOPT_SSL_VERIFYHOST => 2`). Per-call timeout configurable via `RequestOptions`. Maps HTTP status → exception subclass.
- `Http\Request` — value object: `method`, `url`, `headers`, `body` (already-encoded JSON string or null). Immutable; `withHeader()` derivation.
- `Http\Response` — value object: `statusCode`, `headers`, `body`, `debugId()` accessor that pulls `PAYPAL-DEBUG-ID` header.
- `Http\Prefer` — enum: `REPRESENTATION`, `MINIMAL`. Returns the right header value string.
- `Http\RequestOptions` — per-call options: `idempotencyKey`, `prefer` (Prefer enum), `authAssertion` (JWT string), `customHeaders` (array). Immutable. The `partnerAttributionId` is NOT configurable here — that's hardcoded via `Support\PartnerAttribution::VALUE`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Http/TransportInterface.php` | NEW | Single method `send()` |
| `src/REST/Http/CurlTransport.php` | NEW | Default impl, SSL ON, status-to-exception mapping |
| `src/REST/Http/Request.php` | NEW | Immutable VO |
| `src/REST/Http/Response.php` | NEW | Immutable VO + `debugId()` |
| `src/REST/Http/Prefer.php` | NEW | Enum |
| `src/REST/Http/RequestOptions.php` | NEW | Immutable per-call options |
| `tests/Unit/REST/Http/CurlTransportTest.php` | NEW | Uses local HTTP test double or wiremock |
| `tests/Unit/REST/Http/RequestOptionsTest.php` | NEW | Idempotency-key, prefer, derivation |
| `tests/Unit/REST/Http/ResponseTest.php` | NEW | `debugId()` accessor against fixture headers |

## Acceptance criteria

- [ ] `CurlTransport::send(...)` succeeds on a mocked 200 response and returns a `Response` with the expected body + status + debug_id.
- [ ] `CurlTransport::send(...)` throws `AuthenticationException` on 401, `AuthorizationException` on 403, `ResourceNotFoundException` on 404, `RateLimitException` on 429, `ServerException` on 5xx.
- [ ] `RequestOptions::withIdempotencyKey('abc')` produces an instance whose `customHeaders()` includes `PayPal-Request-Id: abc`.
- [ ] `Prefer::REPRESENTATION->toHeaderValue() === 'return=representation'`.
- [ ] SSL verify defaults to ON (verified by inspecting the cURL options set).
- [ ] `Response::debugId()` returns the value of the `PAYPAL-DEBUG-ID` header (case-insensitive).
- [ ] PHPStan level 5 clean.
- [ ] PHPUnit Unit suite coverage ≥80% on these files.

## Verification

```bash
composer test -- --filter 'REST\\Http'
composer phpstan -- --paths=src/REST/Http/
```

## References

- PRD: [§3 REST/Http/ in file structure](../../PRD.md#proposed-file-structure), [§3 Security / SSL verification](../../PRD.md#security--privacy)
- Upstream: [`01-config-and-exceptions.md`](01-config-and-exceptions.md) (exceptions used here)
- Downstream: [`03-auth-and-tokens.md`](03-auth-and-tokens.md), [`04-base-classes-and-facade.md`](04-base-classes-and-facade.md)
