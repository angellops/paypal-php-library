# Phase 0.4 — Test scaffolding, CI workflow, PHPStan

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 0 · **Issue:** TBD · **PRD sections:** [§4 Phase 0](../../PRD.md#phased-rollout), [§5 Verification Strategy](../../PRD.md#5-verification-strategy)

## Context

The repo has no `tests/` directory, no `phpunit.xml`, no CI config, and no static analysis on `main` today. v4.0 ships ≥80% coverage on the new namespaces and CI green as a release gate, so this plan stands up the entire testing and CI infrastructure: PHPUnit config at repo root, the `tests/{Unit,Integration,Fixtures}/` tree, a GitHub Actions workflow with three jobs (PHPUnit + coverage, PHPStan, sandbox-integration gated on secrets), and `phpstan.neon` at level 5 with an allowed baseline file. After Phase 0.4, every PR runs the full check matrix.

## Scope

- Create `phpunit.xml` at repo root with two test suites (`Unit`, `Integration`) and `<coverage>` config gating REST/Legacy/Support at 80%.
- Create `tests/Unit/`, `tests/Integration/`, `tests/Fixtures/{responses,classic-requests}/` directory tree with `.gitkeep` placeholders.
- Add one smoke unit test confirming `\angelleye\PayPal\PayPal` autoloads, `Support\PartnerAttribution::VALUE` resolves, and `Support\Reference` exposes its arrays.
- Create `.github/workflows/ci.yml` with three jobs running on every PR and on `main`/`v4` pushes:
  - **unit**: `composer test` + coverage upload (or coverage gate via `--coverage-text --coverage-clover`)
  - **phpstan**: `composer phpstan`
  - **integration**: runs only when `PAYPAL_SANDBOX_*` repo secrets are present (uses `if: ${{ secrets.PAYPAL_SANDBOX_CLIENT_ID != '' }}` gate); silently no-ops otherwise so external-contributor PRs don't fail.
- Create `phpstan.neon` at level 5 with `paths: [src]` and `bootstrapFiles: [vendor/autoload.php]`.
- Create empty `phpstan-baseline.neon` (Phase 0 adds nothing to it; later phases may need to baseline temporarily).
- Document the `PAYPAL_SANDBOX_*` secret names in the workflow file header so the maintainer knows what to add via the GitHub repo settings UI.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `phpunit.xml` | NEW | Two suites, coverage config, bootstrap = `vendor/autoload.php` |
| `tests/Unit/Support/PartnerAttributionTest.php` | NEW | Smoke test: assert `VALUE === 'WekoodoLLC_Ecom'` |
| `tests/Unit/Support/ReferenceTest.php` | NEW | Smoke test: assert countries / states / AVS / CVV2 / currencies arrays non-empty and match key fixtures |
| `tests/Integration/.gitkeep` | NEW | Placeholder |
| `tests/Fixtures/responses/.gitkeep` | NEW | Placeholder |
| `tests/Fixtures/classic-requests/.gitkeep` | NEW | Placeholder |
| `.github/workflows/ci.yml` | NEW | Three jobs (unit, phpstan, integration-on-secret) |
| `phpstan.neon` | NEW | Level 5, paths = `src/`, bootstrap = composer autoload |
| `phpstan-baseline.neon` | NEW | Empty |

## Acceptance criteria

- [ ] `composer test` runs PHPUnit and the smoke tests pass.
- [ ] `composer phpstan` runs PHPStan at level 5 on `src/` and passes (or its output matches the baseline).
- [ ] `.github/workflows/ci.yml` syntax-validates via `act` or by pushing a no-op commit to a feature branch.
- [ ] On a PR with `PAYPAL_SANDBOX_*` secrets unset, the integration job is skipped (not failed).
- [ ] On a PR from a branch in the same repo (where secrets are accessible), the integration job runs and the noop case is green.
- [ ] CI is green on the Phase 0 PR before merge.

## Verification

```bash
composer test
composer phpstan

# After pushing to a feature branch:
gh run list --branch <branch> --limit 1
gh run view --log <run-id>
```

## References

- PRD: [§4 Phase 0 CI bullet](../../PRD.md#phased-rollout), [§5 Unit Tests / Integration Tests](../../PRD.md#5-verification-strategy)
- Upstream plans: [`03-composer-and-autoload.md`](03-composer-and-autoload.md) (provides PHPUnit + PHPStan dev deps)
- Downstream plans: every subsequent phase relies on green CI
- OOB action for maintainer: add `PAYPAL_SANDBOX_CLIENT_ID`, `PAYPAL_SANDBOX_CLIENT_SECRET`, `PAYPAL_SANDBOX_API_USERNAME`, `PAYPAL_SANDBOX_API_PASSWORD`, `PAYPAL_SANDBOX_API_SIGNATURE` as repo secrets in GitHub settings
