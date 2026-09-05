# Phase 3.3 — Express Checkout mappers

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

The three most-used Classic Express Checkout methods. `SetExpressCheckout` creates the order and returns a redirect URL; `GetExpressCheckoutDetails` fetches what the buyer approved; `DoExpressCheckoutPayment` captures the payment. Together these cover ~80% of merchant Classic call volume — the canonical mapper set.

## Scope

- `Legacy\Mappers\SetExpressCheckoutMapper`:
  - `toRestRequest(array $DataArray): array` — builds a `POST /v2/checkout/orders` body from the NVP fields. Mints a synthetic EC-XXX token from the returned `order_id` via `EcTokenBridge`. Rewrites the merchant's `returnurl` to include the synthetic token (so the EC-XXX form survives the PayPal redirect roundtrip).
  - `toClassicResponse(array $restResponse): array` — returns NVP-shaped `['ACK' => 'Success', 'TOKEN' => 'EC-XXX', 'REDIRECTURL' => 'https://paypal.com/checkoutnow?token=...', ...]`.
- `Legacy\Mappers\GetExpressCheckoutDetailsMapper`:
  - `toRestRequest($DataArray)` — looks up the order_id from the EC-XXX token via `EcTokenBridge::resolveToOrderId`, builds `GET /v2/checkout/orders/{order_id}`.
  - `toClassicResponse($restResponse)` — synthesizes NVP fields like `PAYERID`, `EMAIL`, `FIRSTNAME`, `LASTNAME`, `COUNTRYCODE`, `SHIPTONAME`, `SHIPTOSTREET`, etc.
- `Legacy\Mappers\DoExpressCheckoutPaymentMapper`:
  - `toRestRequest($DataArray)` — looks up order_id, builds `POST /v2/checkout/orders/{order_id}/capture`.
  - `toClassicResponse($restResponse)` — synthesizes `PAYMENTINFO_0_TRANSACTIONID` (= REST capture_id), `PAYMENTINFO_0_PAYMENTSTATUS`, `PAYMENTINFO_0_AMT`, etc. Invalidates the EC-XXX token via `EcTokenBridge::invalidate()` after success.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/SetExpressCheckoutMapper.php` | NEW | |
| `src/Legacy/Mappers/GetExpressCheckoutDetailsMapper.php` | NEW | |
| `src/Legacy/Mappers/DoExpressCheckoutPaymentMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/SetExpressCheckoutMapperTest.php` | NEW | Round-trip: NVP in → REST out → mock REST response → NVP-shaped out |
| `tests/Unit/Legacy/Mappers/GetExpressCheckoutDetailsMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoExpressCheckoutPaymentMapperTest.php` | NEW | |
| `tests/Fixtures/classic-requests/SetExpressCheckout-typical.json` | NEW | Captured NVP request shape |
| `tests/Fixtures/classic-requests/DoExpressCheckoutPayment-typical.json` | NEW | |
| `tests/Fixtures/responses/orders-create-201.json` | EXISTS | Reuse from Phase 2.1 |

## Acceptance criteria

- [ ] `SetExpressCheckoutMapper::toClassicResponse()` returns an array containing `ACK === 'Success'`, `TOKEN` matching `EC-` pattern, `REDIRECTURL` starting with `https://www.paypal.com/checkoutnow?token=` (sandbox: sandbox.paypal.com).
- [ ] `GetExpressCheckoutDetailsMapper::toClassicResponse()` returns at minimum `ACK`, `PAYERID`, `EMAIL`, `FIRSTNAME`, `LASTNAME`, `COUNTRYCODE`.
- [ ] `DoExpressCheckoutPaymentMapper::toClassicResponse()` returns `PAYMENTINFO_0_TRANSACTIONID` equal to the REST capture_id and `PAYMENTINFO_0_PAYMENTSTATUS === 'Completed'` on success.
- [ ] The full round-trip (Set → Get → Do) preserves the EC-XXX token throughout.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'Mappers\\(SetExpress|GetExpress|DoExpress)'
```

## References

- PRD: [§2.3 User Flow](../../PRD.md#user-flow--the-upgrade-experience), [§5 Manual Demo Verification #2](../../PRD.md#manual-demo-verification)
- Upstream: [`01-legacy-foundation.md`](01-legacy-foundation.md), [`02-ec-token-bridge.md`](02-ec-token-bridge.md), Phase 2 Orders + Payments
- Downstream: [`11-paypal-php-dispatch-hook.md`](11-paypal-php-dispatch-hook.md)
