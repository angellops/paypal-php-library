# Phase 3.10 — Other mappers (ManagePendingTransactionStatus, GetBalance)

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

The two miscellaneous mappers that don't cluster into any other group. Both are simple wrappers.

## Scope

- `Legacy\Mappers\ManagePendingTransactionStatusMapper` — Classic `ManagePendingTransactionStatus` (Accept / Deny pending transactions) → REST behaves slightly differently here; the mapper documents the closest equivalent (e.g., for "Accept", let the auto-capture complete; for "Deny", call void). May surface as `UnmappableMethodException` in some edge cases — mapper documents which actions cleanly map vs. which require Classic NVP fallback.
- `Legacy\Mappers\GetBalanceMapper` — Classic `GetBalance` → REST `GET /v1/reporting/balances` (via Reports resource). Returns NVP `BAL_0`, `BAL_0_CURRENCYCODE`, etc.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/ManagePendingTransactionStatusMapper.php` | NEW | |
| `src/Legacy/Mappers/GetBalanceMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/ManagePendingTransactionStatusMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/GetBalanceMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] Round-trip tests pass for both mappers.
- [ ] `ManagePendingTransactionStatusMapper` documents (in code comments + tests) which actions cleanly map.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: Phase 2 Payments + Reports
