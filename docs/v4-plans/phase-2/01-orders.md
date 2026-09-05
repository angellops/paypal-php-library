# Phase 2.1 — Resources\Orders

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Orders](../../PRD.md#proposed-file-structure), [§4 Phase 2](../../PRD.md#phased-rollout)

## Context

The first REST resource handler. Orders v2 (`/v2/checkout/orders`) is PayPal's modern checkout primitive — every Smart Buttons flow, every Pay Later flow, every server-side redirect flow creates an Order. This resource is also the canonical reference for every other Phase 2 resource — the patterns established here (DTO shape, exception mapping, fixture format, doc page format) are repeated 13 times. Extra care on the API surface and tests pays off across all of Phase 2.

## Scope

- `Resources\Orders extends BaseResource` exposing:
  - `create(array $body, ?RequestOptions $opts = null): OrderResponse`
  - `show(string $orderId, ?RequestOptions $opts = null): OrderResponse`
  - `authorize(string $orderId, array $body = [], ?RequestOptions $opts = null): OrderAuthorizationResponse`
  - `capture(string $orderId, array $body = [], ?RequestOptions $opts = null): CaptureResponse`
  - `patch(string $orderId, array $patches, ?RequestOptions $opts = null): void`
- `Responses\OrderResponse` extends `BaseResponse`. Typed accessors: `id()`, `status()`, `links()`, `payer()`, `purchaseUnits()`, plus full `ArrayAccess` to the raw JSON.
- Sandbox integration test: create order with minimal body → assert returned `status === 'CREATED'`, `id` matches pattern, approval link present.
- Unit tests: each of the 5 methods has a happy-path test using a captured fixture and a sad-path test asserting the right exception subclass fires.
- `documentation/rest/orders.md` page with quick-start, full method reference, error handling, link to PayPal's upstream docs.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Orders.php` | NEW | Resource handler |
| `src/REST/Responses/OrderResponse.php` | NEW | Typed DTO |
| `src/REST/Responses/OrderAuthorizationResponse.php` | NEW | Returned by `authorize()` |
| `tests/Unit/REST/Resources/OrdersTest.php` | NEW | Happy + sad paths per method |
| `tests/Integration/REST/OrdersHappyPathTest.php` | NEW | Sandbox-gated |
| `tests/Fixtures/responses/orders-create-201.json` | NEW | Captured |
| `tests/Fixtures/responses/orders-show-200.json` | NEW | Captured |
| `tests/Fixtures/responses/orders-capture-201.json` | NEW | Captured |
| `documentation/rest/orders.md` | NEW | Resource doc |

## Acceptance criteria

- [ ] `Orders::create($body)` builds the correct POST to `/v2/checkout/orders`, body matches input, returns an `OrderResponse`.
- [ ] `OrderResponse::id()` returns the order ID; `ArrayAccess` works for arbitrary path access.
- [ ] Each method has at least one happy-path unit test and one sad-path unit test (e.g., 422 INSTRUMENT_DECLINED → `UnprocessableEntityException`).
- [ ] Sandbox integration test passes when `PAYPAL_SANDBOX_*` secrets are present.
- [ ] PHPStan level 5 clean.
- [ ] Coverage ≥80%.
- [ ] `documentation/rest/orders.md` exists with quick-start code sample.

## Verification

```bash
composer test -- --filter 'REST\\Resources\\Orders'
composer phpstan -- --paths=src/REST/Resources/Orders.php,src/REST/Responses/Order*Response.php
PAYPAL_INTEGRATION_TESTS=1 composer test -- --filter OrdersHappyPath  # requires sandbox creds
```

## References

- PRD: [§2.1 AC2.1-AC2.4](../../PRD.md#user-stories--acceptance-criteria)
- PayPal docs: https://developer.paypal.com/docs/api/orders/v2/
- Upstream: all of Phase 1
- Downstream: Phase 2 sibling resources (this establishes the pattern), Phase 3 mappers that produce REST orders, Phase 4 demos
