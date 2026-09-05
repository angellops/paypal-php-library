# Phase 1.5 — Support utilities (Logger, Json)

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 1 · **Issue:** TBD · **PRD sections:** [§3 Support/](../../PRD.md#proposed-file-structure), [§3 Security & Privacy](../../PRD.md#security--privacy)

## Context

Two shared utilities that the REST layer relies on but that aren't part of any one resource: a PSR-3-compatible `Logger` with a debug_id-aware formatter and secret redaction; and a `Json` wrapper around `json_encode` / `json_decode` with consistent error handling. `Support\PartnerAttribution` already exists from Phase 0; this plan integrates it into REST request headers (verified during `BaseResource` work in 1.4 but spec'd here as the canonical place).

## Scope

- `Support\Logger` — implements PSR-3 `LoggerInterface`. Decorates an underlying logger (NullLogger by default; merchant injects their own via Config). Adds a debug_id-aware formatter: when the log context array contains a `debug_id` key, the formatted line is prefixed with `[debug_id=...]`. Redacts these keys/values from any logged context array: `client_secret`, `Authorization` (header), `access_token`, `api_password`, `api_signature`.
- `Support\Json::encode(mixed $value): string` — throws `ValidationException` on encode failure with `json_last_error_msg()` in the message.
- `Support\Json::decode(string $json): mixed` — throws `ValidationException` on decode failure. Accepts a `$assoc` flag (defaults to `true` since most PayPal responses become associative arrays).
- Smoke test confirming `BaseResource` actually passes `PayPal-Partner-Attribution-Id: WekoodoLLC_Ecom` on every request (this is the canonical end-to-end check that Phase 0's constant flows through to live request headers).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Support/Logger.php` | NEW | PSR-3, debug_id-aware, redacts secrets |
| `src/Support/Json.php` | NEW | Encode + decode with error handling |
| `tests/Unit/Support/LoggerTest.php` | NEW | Redaction + debug_id prefix |
| `tests/Unit/Support/JsonTest.php` | NEW | Encode + decode + error cases |
| `tests/Unit/REST/Resources/BaseResourcePartnerAttributionTest.php` | NEW | Asserts every Request built by BaseResource carries the WekoodoLLC_Ecom header |

## Acceptance criteria

- [ ] `Logger->error('Auth failed', ['debug_id' => 'abc-123', 'client_secret' => 'EH...'])` produces a log line that starts with `[debug_id=abc-123]` and contains NO `EH...` substring (redacted to `client_secret: [REDACTED]`).
- [ ] `Logger` redacts at least these keys: `client_secret`, `Authorization`, `access_token`, `api_password`, `api_signature`.
- [ ] `Json::encode([])` returns `'[]'`; `Json::encode("\xc3\x28")` (invalid UTF-8) throws `ValidationException`.
- [ ] `Json::decode('{"a":1}')` returns `['a' => 1]`; `Json::decode('not json')` throws `ValidationException`.
- [ ] Every `BaseResource` request carries the `PayPal-Partner-Attribution-Id: WekoodoLLC_Ecom` header.
- [ ] PHPStan level 5 clean.
- [ ] Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'Support\\(Logger|Json)'
composer test -- --filter 'BaseResourcePartnerAttribution'
composer phpstan -- --paths=src/Support/
```

## References

- PRD: [§3 Security & Privacy](../../PRD.md#security--privacy) — secret-redaction + debug_id requirements
- Memory: [BN code is hardcoded, not config](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/feedback_partner_attribution_id.md)
- Upstream: [`04-base-classes-and-facade.md`](04-base-classes-and-facade.md), Phase 0's `Support\PartnerAttribution`
- Downstream: every Phase 2 resource uses Logger; mappers in Phase 3 use Json
