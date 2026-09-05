# Phase 0.5 — Phase 0 verification sweep

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 0 · **Issue:** TBD · **PRD sections:** [§4 Phase 0](../../PRD.md#phased-rollout), [§5 Pre-Release Checklist](../../PRD.md#pre-release-checklist)

## Context

Phase 0 is destructive (removes ~13 files, edits `composer.json` and `PayPal.php`) and additive (~10 new files for scaffolding). Before Phase 1 starts adding REST plumbing, we sweep the codebase to confirm Phase 0's invariants hold: dead code is gone, the vendor SDK is gone, the BN-code constant is wired in, reference arrays are extracted, autoload is clean, tests pass, CI is green. This plan file is the gate between Phase 0 and Phase 1.

## Scope

- Code search for every Phase 0 removal target.
- Composer-install on a clean checkout to confirm no `paypal/rest-api-sdk-php/` in `vendor/`.
- Autoload smoke test for `PayPal`, `PayFlow`, `Adaptive`, `Financing`, `Support\PartnerAttribution`, `Support\Reference`.
- Run the full test suite and PHPStan locally.
- Confirm CI is green on the Phase 0 branch.
- Open a PR for the entire Phase 0 changeset (single PR or stacked PRs at the executor's discretion — there's no requirement for one PR per plan file, just one PR per phase or finer if useful).

## Files affected

| Path | Action | Notes |
|---|---|---|
| (none — read-only verification) | — | This plan reads and runs commands; it doesn't change source |

## Acceptance criteria

- [ ] `grep -r 'gtctgyk7fh.execute-api.us-east-2.amazonaws.com' .` returns no hits.
- [ ] `grep -r 'srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa' .` returns no hits.
- [ ] `grep -rE 'TPV_(Parse|Send)_Request' .` returns no hits.
- [ ] `grep -rn '"paypal/rest-api-sdk-php"' composer.json` returns no hits.
- [ ] `grep -rn 'use PayPal\\\\Api\\\\\|use PayPal\\\\Common\\\\\|use PayPal\\\\Exception\\\\' src/` returns no hits.
- [ ] None of the 7 deleted wrapper class files exist on disk.
- [ ] `src/angelleye/PayPal/rest/` does not exist on disk.
- [ ] `composer install --no-dev` on a clean checkout produces no `vendor/paypal/rest-api-sdk-php/` directory.
- [ ] `composer test` exits 0.
- [ ] `composer phpstan` exits 0.
- [ ] GitHub Actions runs for the Phase 0 PR are all green.
- [ ] A merchant performing `composer require angelleye/paypal-php-library:dev-<phase-0-branch>` in a fresh project successfully autoloads `\angelleye\PayPal\PayPal`.

## Verification

```bash
# Single command sweep
bash -c '
set -e
echo "== AWS telemetry removed =="
! grep -rn "gtctgyk7fh\|srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa\|TPV_Parse_Request\|TPV_Send_Request" src/ samples/ demo/ documentation/ 2>/dev/null

echo "== Vendor SDK removed =="
! grep -n "paypal/rest-api-sdk-php" composer.json
! grep -rn "use PayPal\\\\Api\\\\\|use PayPal\\\\Common\\\\\|use PayPal\\\\Exception\\\\" src/

echo "== Orphan classes deleted =="
for f in RestClass CheckoutOrdersClass CustomerDisputesClass EventTypesClass InvoicingClass PayPalSyncClass ReferencedPayoutsClass; do
  [ ! -f "src/angelleye/PayPal/$f.php" ] || { echo "FAIL: $f still exists"; exit 1; }
done

echo "== Tests pass =="
composer test
composer phpstan

echo "== Autoload smoke =="
php -r "require \"vendor/autoload.php\"; new \\angelleye\\PayPal\\PayPal([]); echo \\angelleye\\PayPal\\Support\\PartnerAttribution::VALUE;"
echo

echo "OK"
'
```

## References

- PRD: [§5 Pre-Release Checklist](../../PRD.md#pre-release-checklist) for the canonical list of zero-hit checks
- Upstream plans: every other Phase 0 file (`01` through `04`)
- Downstream plans: [Phase 1](../phase-1/) — gated on Phase 0 verification passing
