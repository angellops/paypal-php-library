# Phase 3.12 — paypal-upgrade-check CLI

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§2.1 AC1.5](../../PRD.md#user-stories--acceptance-criteria), [§3 file structure / bin/](../../PRD.md#proposed-file-structure)

## Context

Standalone CLI that scans a merchant's source tree and classifies every Classic method call site into 4 buckets: cleanly-upgradable, upgradable-with-caveats, auto-fallback-to-Classic, unmappable (no Classic creds available). AST-based via `nikic/php-parser` (Phase 0 dev dep). The tool is what merchants run BEFORE flipping `upgrade_from_classic = true` so they know what to expect.

## Scope

- `bin/paypal-upgrade-check` — Linux/Mac executable PHP script. Usage: `vendor/bin/paypal-upgrade-check <path>` (path = source tree to scan).
- Uses `nikic/php-parser` to build the AST of every PHP file under the given path.
- Identifies method calls on instances of `\angelleye\PayPal\PayPal`. Handles `$paypal->Method(...)` direct calls; documents limitations for dynamic invocations (`call_user_func`, etc.).
- For each found call site, classifies the method into one of the 4 buckets:
  - **cleanly-upgradable**: method has a mapper, no documented caveats.
  - **upgradable-with-caveats**: method has a mapper but with behavioral differences (MassPay async, recurring orchestration, etc.).
  - **auto-fallback-to-Classic**: method has no mapper but is in `UnmappableMethods::list()`; the SDK will auto-fallback to Classic NVP at runtime if Classic creds are present.
  - **unmappable**: method has no mapper and Classic NVP doesn't cover it either (a method removed in some prior Classic API version, or just not in the SDK).
- Outputs a markdown report: file path, line number, method, classification, recommended action (link to PayPal-side alternative for unmappable; link to upgrade-from-classic doc anchor for upgradable-with-caveats).
- Adds `bin/paypal-upgrade-check` to `composer.json`'s `bin` array.
- `chmod +x bin/paypal-upgrade-check` so it's executable after `composer install`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `bin/paypal-upgrade-check` | NEW | Executable PHP CLI |
| `composer.json` | EDIT | Add to `bin` array |
| `tests/Fixtures/sample-merchant-codebases/` | NEW | Several seeded PHP files covering all 4 classification cases |
| `tests/Unit/Cli/UpgradeCheckTest.php` | NEW | Runs the CLI against fixtures, asserts classification accuracy |

## Acceptance criteria

- [ ] `vendor/bin/paypal-upgrade-check tests/Fixtures/sample-merchant-codebases/` runs without error.
- [ ] Output includes at least one entry in each of the 4 buckets from the seeded fixtures.
- [ ] Classifications match expected for each fixture file.
- [ ] CLI documents the dynamic-invocation limitation in its `--help`.
- [ ] PHPStan level 5 clean on the CLI script.
- [ ] After `composer install`, `vendor/bin/paypal-upgrade-check` is in the PATH and executable.

## Verification

```bash
composer install
vendor/bin/paypal-upgrade-check tests/Fixtures/sample-merchant-codebases/
composer test -- --filter UpgradeCheck
```

## References

- PRD: [§2.1 AC1.5](../../PRD.md#user-stories--acceptance-criteria), [§5 Manual Demo Verification #5](../../PRD.md#manual-demo-verification)
- Upstream: [`01-legacy-foundation.md`](01-legacy-foundation.md) (UnmappableMethods list), Phase 0 dev deps
