# Phase 3.4 — Auth / Capture / Void mappers

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

The post-Express-Checkout payment lifecycle mappers. These wrap REST Payments v2 operations to preserve Classic merchant call shapes for authorization-flow integrations.

## Scope

- `Legacy\Mappers\DoAuthorizationMapper` — maps Classic `DoAuthorization` to REST `POST /v2/payments/authorizations/{auth_id}` flow (or Orders v2 `authorize` if the order is still in CREATED state).
- `Legacy\Mappers\DoCaptureMapper` — Classic `DoCapture` → REST `POST /v2/payments/authorizations/{auth_id}/capture`.
- `Legacy\Mappers\DoReauthorizationMapper` — `POST /v2/payments/authorizations/{auth_id}/reauthorize`.
- `Legacy\Mappers\DoVoidMapper` — `POST /v2/payments/authorizations/{auth_id}/void`.
- Each mapper synthesizes the NVP envelope (ACK / CORRELATIONID / TIMESTAMP / VERSION) plus method-specific fields (e.g., `AUTHORIZATIONID`, `TRANSACTIONID`, `AMT`, `PAYMENTSTATUS`).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/DoAuthorizationMapper.php` | NEW | |
| `src/Legacy/Mappers/DoCaptureMapper.php` | NEW | |
| `src/Legacy/Mappers/DoReauthorizationMapper.php` | NEW | |
| `src/Legacy/Mappers/DoVoidMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoAuthorizationMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoCaptureMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoReauthorizationMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/DoVoidMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] Each mapper's `toRestRequest` produces a REST request shape that matches a captured-from-production fixture.
- [ ] Each mapper's `toClassicResponse` produces NVP-shaped output containing the canonical fields a Classic merchant's code path reads.
- [ ] Round-trip tests pass for all four.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: [`01-legacy-foundation.md`](01-legacy-foundation.md), Phase 2 Payments
- PayPal Classic docs: NVP API reference for `DoAuthorization`, `DoCapture`, `DoReauthorization`, `DoVoid`
