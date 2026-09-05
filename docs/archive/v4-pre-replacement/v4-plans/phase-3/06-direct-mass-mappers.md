# Phase 3.6 — DoDirectPayment + MassPay mappers

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

Two mappers covering disparate Classic methods that don't fit into other categories. `DoDirectPayment` (direct credit card) maps to REST Orders v2 with `payment_source.card`. `MassPay` (bulk payouts) maps to REST Payouts v1, with documented behavioral differences (async batch, item-level tracking).

## Scope

- `Legacy\Mappers\DoDirectPaymentMapper` — Classic `DoDirectPayment` → REST `POST /v2/checkout/orders` with `intent: CAPTURE` + `payment_source.card`. Returns NVP-shaped `TRANSACTIONID`, `AMT`, etc. **Note:** PayPal restricts direct-card processing to merchants with the right account configuration; the mapper should surface clear errors when the merchant isn't enabled for this.
- `Legacy\Mappers\MassPayMapper` — Classic `MassPay` → REST `POST /v1/payments/payouts`. Behavioral differences documented in code comments AND in `documentation/upgrade-from-classic.md`: REST Payouts is async (returns a batch_id, items are processed asynchronously), Classic MassPay was synchronous. The mapper synthesizes a Classic-shaped synchronous response by polling the batch briefly OR returning immediately with a flag indicating the merchant should query individually.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/DoDirectPaymentMapper.php` | NEW | |
| `src/Legacy/Mappers/MassPayMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoDirectPaymentMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/MassPayMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] `DoDirectPaymentMapper::toClassicResponse()` returns `TRANSACTIONID`, `AMT`, `CURRENCYCODE`, `PAYMENTSTATUS`, `ACK`.
- [ ] `DoDirectPaymentMapper` surfaces a clear error when the merchant isn't enabled for direct-card processing.
- [ ] `MassPayMapper::toClassicResponse()` returns `ACK === 'Success'` and includes batch tracking info; doc comments and the Phase 5 upgrade doc spell out the async-vs-sync behavioral change.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: Phase 2 Orders + Payouts
