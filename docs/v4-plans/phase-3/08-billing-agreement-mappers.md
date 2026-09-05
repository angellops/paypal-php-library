# Phase 3.8 — Billing Agreement mappers

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

Classic Billing Agreements (the older "payment agreement" surface, distinct from Recurring Profiles) map to REST Vault v3 payment-tokens. The merchant creates an agreement; the buyer approves; the merchant uses the resulting token for future charges.

## Scope

- `Legacy\Mappers\CreateBillingAgreementMapper` — Classic `CreateBillingAgreement` → REST `POST /v3/vault/setup-tokens` to start the flow, returns NVP token + redirect URL. After the buyer returns, a follow-up call creates the persistent payment-token.
- `Legacy\Mappers\BillAgreementUpdateMapper` — Classic `BillAgreementUpdate` → REST `PUT /v3/vault/payment-tokens/{token_id}` for description updates, or `DELETE` for cancellation.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/CreateBillingAgreementMapper.php` | NEW | |
| `src/Legacy/Mappers/BillAgreementUpdateMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/CreateBillingAgreementMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/BillAgreementUpdateMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] Round-trip tests pass for both mappers.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: Phase 2 Vault
