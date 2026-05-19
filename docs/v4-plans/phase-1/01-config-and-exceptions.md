# Phase 1.1 — REST\Config + Exceptions

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§3 file structure / REST/](../../PRD.md#proposed-file-structure), [§4 Phase 1](../../PRD.md#phased-rollout)

## Context

Phase 1 builds the REST transport plumbing. Before any HTTP or OAuth code, two foundation pieces: an immutable `Config` value object that holds client credentials, sandbox toggle, base URLs, transport injection, token store injection, and logger; and a full `Exceptions\*` hierarchy that every downstream component throws into. These two land together because each subsequent Phase 1 plan needs both (HTTP transport throws transport exceptions; OAuth throws auth exceptions; resources throw API exceptions).

## Scope

- `REST\Config` as an immutable value object. Validates required keys (`ClientID`, `ClientSecret`) at construction time, throws `ConfigurationException` if missing. Exposes typed accessors: `clientId()`, `clientSecret()`, `isSandbox()`, `baseUrl()`, `transport()`, `tokenStore()`, `logger()`. Default transport = `CurlTransport`. Default token store = `InMemoryTokenStore`. Default logger = a NullLogger (PSR-3). No setters — all configuration passed via the constructor; use `withTransport()`-style methods for derivation.
- `REST\Exceptions\PayPalException` — abstract base.
- `REST\Exceptions\PayPalApiException` — concrete. Carries `$debugId`, `$errorName`, `$details[]`, `$statusCode`. The mother exception for everything PayPal-API-related.
- `REST\Exceptions\AuthenticationException` (401), `AuthorizationException` (403), `ResourceNotFoundException` (404), `ResourceConflictException` (409, PREVIOUS_REQUEST_IN_PROGRESS), `UnprocessableEntityException` (422, INSTRUMENT_DECLINED + validation), `RateLimitException` (429, surfaces `Retry-After`), `ServerException` (5xx). All extend `PayPalApiException`.
- `REST\Exceptions\TransportException` — cURL / network errors before any HTTP status code.
- `REST\Exceptions\ValidationException` — client-side validation, before HTTP.
- `REST\Exceptions\ConfigurationException` — missing client_id, bad sandbox flag, etc.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Config.php` | NEW | Immutable VO, validates required keys |
| `src/REST/Exceptions/PayPalException.php` | NEW | Abstract base |
| `src/REST/Exceptions/PayPalApiException.php` | NEW | Carries debug_id + errorName + details + statusCode |
| `src/REST/Exceptions/AuthenticationException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/AuthorizationException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/ResourceNotFoundException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/ResourceConflictException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/UnprocessableEntityException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/RateLimitException.php` | NEW | exposes `retryAfter()` |
| `src/REST/Exceptions/ServerException.php` | NEW | extends PayPalApiException |
| `src/REST/Exceptions/TransportException.php` | NEW | extends PayPalException, not API |
| `src/REST/Exceptions/ValidationException.php` | NEW | client-side |
| `src/REST/Exceptions/ConfigurationException.php` | NEW | thrown by `Config` constructor |
| `tests/Unit/REST/ConfigTest.php` | NEW | Validates required keys, defaults, derivation methods |
| `tests/Unit/REST/Exceptions/PayPalApiExceptionTest.php` | NEW | `debugId()`, `errorName()`, `details()`, `statusCode()` accessors |

## Acceptance criteria

- [ ] `new Config(['ClientID' => 'a', 'ClientSecret' => 'b'])` returns a usable instance with sandbox defaults.
- [ ] `new Config([])` throws `ConfigurationException` with a clear message.
- [ ] `Config::withTransport($custom)` returns a new instance with the custom transport, original untouched.
- [ ] Every exception in `Exceptions\` is autoloadable and exposes the documented accessors.
- [ ] `PayPalApiException::fromResponse($response)` factory builds the right subclass for each HTTP status code (401 → AuthenticationException, 429 → RateLimitException with `Retry-After`, etc.).
- [ ] PHPStan level 5 clean on all new files.
- [ ] PHPUnit Unit suite passes; coverage ≥80% on these files.

## Verification

```bash
composer test -- --filter 'REST\\(Config|Exceptions)'
composer phpstan -- --paths=src/REST/Config.php,src/REST/Exceptions/
```

## References

- PRD: [§3 Architecture file structure / REST/Exceptions/](../../PRD.md#proposed-file-structure)
- Upstream: [Phase 0](../phase-0/) (provides composer + autoload + test scaffolding)
- Downstream: every other Phase 1 plan + all of Phase 2 (resources throw these)
