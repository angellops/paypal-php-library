# Phase 3.13 — Legacy exceptions + auto-fallback dispatch

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/Exceptions/](../../PRD.md#proposed-file-structure), [§2.1 AC1.3](../../PRD.md#user-stories--acceptance-criteria)

## Context

Two exceptions for the legacy adapter layer (`UnmappableMethodException`, `LegacyConfigException`) plus the exact dispatch logic that implements the auto-fallback contract: route to REST if a mapper exists, route to Classic NVP if no mapper but Classic creds are present, throw `UnmappableMethodException` otherwise. This is the cleanup file that pulls the auto-fallback behavior together and writes the contract tests.

## Scope

- `Legacy\Exceptions\UnmappableMethodException` — thrown by `RESTBackend::dispatch` when no mapper exists AND the dispatcher determines auto-fallback isn't possible. Carries the method name + a recommended PayPal-side alternative.
- `Legacy\Exceptions\LegacyConfigException` — thrown by the `RESTBackend` constructor when invalid config is supplied (e.g., `upgrade_from_classic = true` but `ClientID` missing).
- Refine the `RESTBackend` dispatch logic:
  ```
  if mapper exists for $method:
      use mapper → REST request → response shaper → return NVP-shaped array
  elif Classic creds present AND $method in UnmappableMethods::list():
      // Auto-fallback: do nothing; the dispatcher returns null, signaling
      // PayPal.php's prepended hook to fall through to existing Classic NVP impl
      return null
  else:
      throw UnmappableMethodException("$method has no REST equivalent; provide Classic creds or rewrite the call site")
  ```
- Update the PayPal.php hook prepend to handle the null-return: `$result = $this->backend->dispatch(...); if ($result !== null) return $result; // fall through to Classic NVP`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/Exceptions/UnmappableMethodException.php` | NEW | |
| `src/Legacy/Exceptions/LegacyConfigException.php` | NEW | |
| `src/Legacy/RESTBackend.php` | EDIT | Refine dispatch logic per the contract above |
| `src/angelleye/PayPal/PayPal.php` | EDIT | Update the hook prepend to handle null-return from dispatch (auto-fallback signal) |
| `tests/Unit/Legacy/AutoFallbackContractTest.php` | NEW | Tests the full contract: 3 cases × multiple methods |
| `tests/Unit/Legacy/Exceptions/UnmappableMethodExceptionTest.php` | NEW | Carries method name + alternative |

## Acceptance criteria

- [ ] `RESTBackend::dispatch('SetExpressCheckout', [...])` returns a NVP-shaped array (mapper exists).
- [ ] `RESTBackend::dispatch('BMCreateButton', [...])` with Classic creds present returns null (auto-fallback signal).
- [ ] `RESTBackend::dispatch('BMCreateButton', [...])` with Classic creds absent throws `UnmappableMethodException`.
- [ ] `UnmappableMethodException` message names a specific recommended PayPal-side alternative when one exists.
- [ ] `PayPal.php` correctly falls through to Classic NVP when dispatch returns null.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'AutoFallbackContract|UnmappableMethodException'
```

## References

- PRD: [§2.1 AC1.3](../../PRD.md#user-stories--acceptance-criteria) — auto-fallback contract
- Upstream: every Phase 3 file before this one
- Downstream: Phase 4 demos (relies on auto-fallback working), Phase 6 manual demo verifications
