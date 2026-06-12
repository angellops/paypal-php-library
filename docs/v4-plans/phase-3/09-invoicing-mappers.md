# Phase 3.9 — Invoicing mappers

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Mappers/](../../PRD.md#proposed-file-structure)

## Context

Five mappers covering the Classic invoicing methods. Each is a thin wrapper around the corresponding REST Invoicing v2 endpoint (Phase 2.8 resource).

## Scope

- `Legacy\Mappers\CreateInvoiceMapper` — `POST /v2/invoicing/invoices` to create a draft.
- `Legacy\Mappers\SendInvoiceMapper` — `POST /v2/invoicing/invoices/{id}/send`.
- `Legacy\Mappers\GetInvoiceDetailsMapper` — `GET /v2/invoicing/invoices/{id}`.
- `Legacy\Mappers\UpdateInvoiceMapper` — `PUT /v2/invoicing/invoices/{id}`.
- `Legacy\Mappers\CancelInvoiceMapper` — `POST /v2/invoicing/invoices/{id}/cancel`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Mappers/CreateInvoiceMapper.php` | NEW | |
| `src/Legacy/Mappers/SendInvoiceMapper.php` | NEW | |
| `src/Legacy/Mappers/GetInvoiceDetailsMapper.php` | NEW | |
| `src/Legacy/Mappers/UpdateInvoiceMapper.php` | NEW | |
| `src/Legacy/Mappers/CancelInvoiceMapper.php` | NEW | |
| `tests/Unit/Legacy/Mappers/CreateInvoiceMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/SendInvoiceMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/GetInvoiceDetailsMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/UpdateInvoiceMapperTest.php` | NEW | |
| `tests/Unit/Legacy/Mappers/CancelInvoiceMapperTest.php` | NEW | |

## Acceptance criteria

- [ ] Round-trip tests pass for all 5 mappers.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- Upstream: Phase 2 Invoicing
