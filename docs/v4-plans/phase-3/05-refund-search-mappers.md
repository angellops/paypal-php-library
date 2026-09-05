# Phase 3.5 — Refund / Search / Reference-transaction mappers

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

Refund, transaction lookup, search, and reference-transaction-payment mappers. `TransactionSearch` is rate-limited at the REST side (Reports API) — the mapper must handle 429s gracefully and surface them as Classic-style errors.

## Scope

- `Legacy\Mappers\RefundTransactionMapper` — Classic `RefundTransaction` → REST `POST /v2/payments/captures/{capture_id}/refund`.
- `Legacy\Mappers\GetTransactionDetailsMapper` — Classic `GetTransactionDetails` → REST `GET /v2/payments/captures/{capture_id}` (or `/authorizations/{auth_id}` based on the transaction id pattern; the mapper tries capture first, falls back to authorization).
- `Legacy\Mappers\TransactionSearchMapper` — Classic `TransactionSearch` → REST `GET /v1/reporting/transactions`. Handles 429 → returns NVP `ACK === 'Failure'` with `L_ERRORCODE0` corresponding to a rate-limit error code.
- `Legacy\Mappers\DoReferenceTransactionMapper` — Classic `DoReferenceTransaction` → REST `POST /v2/checkout/orders` with `payment_source.token` referencing a vaulted payment token (uses Vault v3).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/RefundTransactionMapper.php` | NEW | |
| `src/Legacy/Mappers/GetTransactionDetailsMapper.php` | NEW | |
| `src/Legacy/Mappers/TransactionSearchMapper.php` | NEW | |
| `src/Legacy/Mappers/DoReferenceTransactionMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/RefundTransactionMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/GetTransactionDetailsMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/TransactionSearchMapperTest.php` | NEW | Includes 429 handling test |
| `tests/Unit/Legacy/Mappers/DoReferenceTransactionMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] `RefundTransactionMapper::toClassicResponse()` returns `REFUNDTRANSACTIONID`, `FEEREFUNDAMT`, `GROSSREFUNDAMT`, `NETREFUNDAMT`, `ACK`, etc.
- [ ] `GetTransactionDetailsMapper` correctly resolves both capture_id-shaped and authorization_id-shaped inputs.
- [ ] `TransactionSearchMapper` 429 handling: rate-limit response → NVP `ACK === 'Failure'`, `L_ERRORCODE0` set to a documented Classic error code, `L_SHORTMESSAGE0` describes the rate limit.
- [ ] `DoReferenceTransactionMapper` round-trips via the Vault v3 payment token reference.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: [`01-legacy-foundation.md`](01-legacy-foundation.md), Phase 2 Payments + Reports + Vault
