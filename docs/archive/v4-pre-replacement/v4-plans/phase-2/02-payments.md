# Phase 2.2 — Resources\Payments

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Payments](../../PRD.md#proposed-file-structure)

## Context

Payments v2 (`/v2/payments/*`) covers the post-Orders lifecycle: authorizations, captures, refunds. An Order creates an authorization or capture; Payments operates on those after creation (reauthorize, capture an existing authorization, refund a capture, void an uncaptured authorization). Same pattern as Orders — methods, typed DTOs, fixtures, sandbox happy path, doc page.

## Scope

- `Resources\Payments` exposing:
  - `showAuthorization(string $authId): AuthorizationResponse`
  - `captureAuthorization(string $authId, array $body, ?RequestOptions $opts = null): CaptureResponse`
  - `reauthorizeAuthorization(string $authId, ?array $body = null): AuthorizationResponse`
  - `voidAuthorization(string $authId): void`
  - `showCapture(string $captureId): CaptureResponse`
  - `refundCapture(string $captureId, array $body, ?RequestOptions $opts = null): RefundResponse`
  - `showRefund(string $refundId): RefundResponse`
- `Responses\AuthorizationResponse`, `CaptureResponse`, `RefundResponse` — typed DTOs with `id()`, `status()`, `amount()`, etc.
- Unit + sandbox integration tests, doc page.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Payments.php` | NEW | Resource handler |
| `src/REST/Responses/AuthorizationResponse.php` | NEW | |
| `src/REST/Responses/CaptureResponse.php` | NEW | |
| `src/REST/Responses/RefundResponse.php` | NEW | |
| `tests/Unit/REST/Resources/PaymentsTest.php` | NEW | All 7 methods |
| `tests/Integration/REST/PaymentsHappyPathTest.php` | NEW | Sandbox-gated end-to-end (depends on Orders to create the auth first) |
| `tests/Fixtures/responses/payments-*.json` | NEW | One per method |
| `documentation/rest/payments.md` | NEW | Resource doc |

## Acceptance criteria

- [ ] All 7 methods build correct REST requests and return typed DTOs.
- [ ] Sad-path tests cover at least: capture-already-completed (422), refund-exceeds-capture (422), void-on-captured-auth (409 or 422), authorization-not-found (404).
- [ ] Sandbox integration test creates an Order, authorizes it, captures, refunds — full chain green.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'REST\\Resources\\Payments'
```

## References

- PayPal docs: https://developer.paypal.com/docs/api/payments/v2/
- Upstream: [`01-orders.md`](01-orders.md) for the resource-handler pattern
