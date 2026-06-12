# Phase 6.2 — RC tag + 1-week bake

**Phase:** 6 · **Issue:** TBD · **PRD sections:** [§4 Phase 6](../../PRD.md#phased-rollout), [§5 Pre-Release Checklist](../../PRD.md#pre-release-checklist)

## Context

After the manual demo verifications pass, run through the full pre-release checklist one final time, tag `v4.0.0-rc1` on `angellops/paypal-php-library` (the GA package rename happens in Phase 7), publish the RC to Packagist, and start the 1-week bake window. If any sandbox regression surfaces during the bake, fix and re-tag (`rc2`, `rc3`, …) until a clean RC bakes without regressions.

## Scope

- Sweep the Pre-Release Checklist (PRD §5):
  - All Unit tests pass (`composer test`).
  - All sandbox Integration tests pass.
  - Coverage report ≥ 80% on new namespaces.
  - All 5 manual demo verifications pass (per Phase 6.1).
  - Code searches for removed symbols return zero hits.
  - `$APIButtonSource === \angelleye\PayPal\Support\PartnerAttribution::VALUE`.
  - CI is green on the `v4` branch tip.
- Merge `v4` to `main` (or stay on `v4` for the RC tag — executor's call; the PRD anticipates merging at GA, so the RC tag can be on `v4` if desired).
- Tag `v4.0.0-rc1` on the appropriate branch.
- Push the tag. Packagist auto-publishes via webhook.
- Document the RC announcement: post to whatever channels merchants watch (Twitter/X, GitHub Discussions, PRD-referenced contact list).
- Start the 1-week clock. Use the bundled demos as the canary; optionally invite 1-2 beta merchants for production-flow validation (per OOB item 4).
- If the bake completes cleanly, Phase 6 is done. Phase 7 starts.

## Files affected

| Path | Action | Notes |
|---|---|---|
| (none directly — verification + tag) | — | |
| Git tag `v4.0.0-rc1` | NEW | On `angellops/paypal-php-library` |

## Acceptance criteria

- [ ] Every item in the PRD §5 Pre-Release Checklist is checked off (or explicitly noted as deferred to Phase 7 — e.g., the GitHub redirect / Packagist abandoned items naturally defer).
- [ ] `v4.0.0-rc1` tag exists and Packagist shows it as a publishable RC.
- [ ] The 1-week bake completes without sandbox regressions in the bundled demos.
- [ ] RC announcement is posted to merchant-facing channels.

## Verification

```bash
git tag --list | grep v4.0.0
gh release view v4.0.0-rc1
composer show angelleye/paypal-php-library --all | grep '4.0.0-RC'
```

## References

- PRD: [§4 Phase 6](../../PRD.md#phased-rollout), [§5 Pre-Release Checklist](../../PRD.md#pre-release-checklist)
- Upstream: [`01-manual-demo-verifications.md`](01-manual-demo-verifications.md)
- Downstream: [Phase 7](../phase-7/)
