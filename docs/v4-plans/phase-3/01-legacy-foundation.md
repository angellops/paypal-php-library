# Phase 3.1 — Legacy adapter foundation

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/](../../PRD.md#proposed-file-structure), [§4 Phase 3](../../PRD.md#phased-rollout)

## Context

Phase 3 is the upgrade path: existing Classic NVP merchants flip `upgrade_from_classic = true` and their existing `$PayPal->SetExpressCheckout(...)` calls transparently route through REST. This plan builds the foundation classes that all 30 mappers depend on. After Phase 3.1 lands, the 9 mapper plan files (3.3 through 3.10) can be implemented in parallel by separate sessions.

## Scope

- `Legacy\RESTBackend` — the dispatcher. Holds a `REST\Client`. Method `dispatch(string $classicMethod, array $DataArray): array` routes to the right mapper by classic method name. Method `canDispatch(string $classicMethod): bool` returns whether a mapper exists for that method.
- `Legacy\ResponseShaper` — REST JSON → NVP-shaped array helpers. Synthesizes `ACK`, `CORRELATIONID`, `TIMESTAMP`, `VERSION` from the REST response's debug_id + current time. Generic helpers like `nvpField(string $key, $value): array` used by every mapper.
- `Legacy\ErrorTranslator` — converts a thrown `PayPalApiException` into the NVP `ERRORS` array shape Classic merchants expect (`L_ERRORCODE0`, `L_SHORTMESSAGE0`, `L_LONGMESSAGE0`, etc.).
- `Legacy\UnmappableMethods` — static list of Classic method names with no REST equivalent. Used both by the dispatcher (to fall through to Classic NVP when creds are present) and by the upgrade-check CLI.
- `Legacy\Mappers\MapperInterface` — single contract: `toRestRequest(array $DataArray): array` returning REST body; `toClassicResponse(array $restResponse): array` returning NVP-shaped output.
- `Legacy\Mappers\FieldMap` — class with constant arrays for simple 1:1 leaf mappings (~200 entries: `AMT` ↔ `amount.value`, `CURRENCYCODE` ↔ `amount.currency_code`, etc.). Used by individual mappers to avoid duplicating leaf logic.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/RESTBackend.php` | NEW | Dispatcher |
| `src/Legacy/ResponseShaper.php` | NEW | NVP shape synthesis |
| `src/Legacy/ErrorTranslator.php` | NEW | Exception → ERRORS array |
| `src/Legacy/UnmappableMethods.php` | NEW | Static list |
| `src/Legacy/Mappers/MapperInterface.php` | NEW | Two-method contract |
| `src/Legacy/Mappers/FieldMap.php` | NEW | ~200 leaf-key entries |
| `tests/Unit/Legacy/RESTBackendTest.php` | NEW | Routing, canDispatch |
| `tests/Unit/Legacy/ResponseShaperTest.php` | NEW | Each synthesized field shape |
| `tests/Unit/Legacy/ErrorTranslatorTest.php` | NEW | Each exception type |
| `tests/Unit/Legacy/UnmappableMethodsTest.php` | NEW | List membership |
| `tests/Unit/Legacy/Mappers/FieldMapTest.php` | NEW | Entry correctness sample |

## Acceptance criteria

- [ ] `RESTBackend::canDispatch('SetExpressCheckout') === true`, `canDispatch('BMCreateButton') === false`.
- [ ] `ResponseShaper::synthesizeEnvelope($restResponse)` returns an array with `ACK`, `CORRELATIONID` (= debug_id), `TIMESTAMP` (ISO 8601), `VERSION` (matching the configured Classic API version).
- [ ] `ErrorTranslator::translate(new UnprocessableEntityException(...))` returns an array with `ACK === 'Failure'`, `L_ERRORCODE0`, `L_SHORTMESSAGE0`, `L_LONGMESSAGE0`, `L_SEVERITYCODE0 === 'Error'`.
- [ ] `UnmappableMethods::list()` is a non-empty array containing at least `BMCreateButton`, `DoNonReferencedCredit`, `AddressVerify`.
- [ ] `FieldMap` exposes constants/methods for at least 100 leaf mappings.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'Legacy\\(RESTBackend|ResponseShaper|ErrorTranslator|UnmappableMethods|Mappers\\FieldMap)'
```

## References

- PRD: [§3 Legacy/ in file structure](../../PRD.md#proposed-file-structure), [§4 Phase 3](../../PRD.md#phased-rollout)
- Upstream: all of Phase 1, all of Phase 2 (the dispatcher needs a working REST Client)
- Downstream: every Phase 3 mapper file
