# Phase 3.11 — PayPal.php dispatch hook

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Architecture Overview](../../PRD.md#architecture-overview), [§4 Phase 3](../../PRD.md#phased-rollout)

## Context

The single most BC-sensitive change in the entire v4.0 effort: adding a dispatch hook to `PayPal.php` that, when `upgrade_from_classic = true`, transparently routes Classic public method calls through the REST backend. Every public method on `PayPal.php` that has a corresponding mapper gets a small prepend at the top: "if `$this->backend->canDispatch(__FUNCTION__)` then return `$this->backend->dispatch(__FUNCTION__, $DataArray)`; else fall through to the existing Classic implementation." There are 30 such methods. The change is surgical — no public method signature changes, no return-shape changes for callers who don't flip the flag.

Also: the constructor needs to detect `upgrade_from_classic = true`, instantiate `Legacy\RESTBackend`, and emit the one-time PSR-3 NOTICE listing which Classic methods will route to Classic NVP via auto-fallback (per the auto-fallback design decision).

## Scope

- Add `private ?\angelleye\PayPal\Legacy\RESTBackend $backend = null;` property to `PayPal`.
- In constructor: detect `$DataArray['upgrade_from_classic']`, instantiate `RESTBackend` using `ClientID` + `ClientSecret`, store in `$this->backend`. If Classic credentials are also present, log via the configured PSR-3 logger a NOTICE listing the auto-fallback methods (sourced from `UnmappableMethods::list()`).
- For each of the 30 mapped public methods, prepend:
  ```php
  if ($this->backend && $this->backend->canDispatch(__FUNCTION__)) {
      return $this->backend->dispatch(__FUNCTION__, $DataArray);
  }
  ```
- The existing Classic NVP code paths remain unchanged for merchants who don't flip the flag.
- For unmappable methods (e.g., `BMCreateButton`): the dispatcher's `canDispatch` returns false, so the existing Classic NVP code runs — that IS the auto-fallback path.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/angelleye/PayPal/PayPal.php` | EDIT | Add `$backend` property, instantiate in constructor, prepend dispatch hook to 30 public methods. Search by method name (the list is in `UnmappableMethods` complement + the mapper list in PRD §3). |
| `tests/Unit/PayPal/DispatchHookTest.php` | NEW | For each of the 30 methods: with `upgrade_from_classic = true` + mocked backend, call the method, assert `RESTBackend::dispatch()` was invoked. Also: with `upgrade_from_classic = false`, assert dispatch was NOT invoked. |
| `tests/Unit/PayPal/AutoFallbackNoticeTest.php` | NEW | With `upgrade_from_classic = true` + Classic creds present + a mocked PSR-3 logger, instantiate `PayPal`, assert one NOTICE log line was emitted listing the unmappable methods. |

## Acceptance criteria

- [ ] All 30 mapped public methods route through `RESTBackend` when `upgrade_from_classic = true`.
- [ ] All public method signatures on `PayPal` are unchanged.
- [ ] When `upgrade_from_classic = false` (default), zero behavior change for callers.
- [ ] When `upgrade_from_classic = true` + Classic creds present + an unmappable method (`BMCreateButton`) is called → call falls through to existing Classic NVP impl, runs as before.
- [ ] When `upgrade_from_classic = true` + Classic creds absent + an unmappable method is called → throws `UnmappableMethodException`.
- [ ] One-time PSR-3 NOTICE is logged on construction (with `upgrade_from_classic = true`) listing the auto-fallback methods.
- [ ] PHPStan level 5 clean.
- [ ] Coverage ≥80% on the modified `PayPal.php` methods.

## Verification

```bash
composer test -- --filter 'DispatchHook|AutoFallbackNotice'
composer phpstan -- --paths=src/angelleye/PayPal/PayPal.php
```

## References

- PRD: [§3 Architecture Overview](../../PRD.md#architecture-overview), [§2.1 AC1.1-AC1.3](../../PRD.md#user-stories--acceptance-criteria)
- Upstream: every Phase 3 file before this one (3.1 through 3.10)
- Downstream: [`12-upgrade-check-cli.md`](12-upgrade-check-cli.md) (CLI references the same mapper list), Phase 6 demo verification
