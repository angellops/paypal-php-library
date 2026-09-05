# Phase 5.4 — README, CHANGELOG, migration, brand-history

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 5 · **Issue:** TBD · **PRD sections:** [§4 Phase 5](../../PRD.md#phased-rollout), [§4 Phase 7](../../PRD.md#phased-rollout)

## Context

The four top-level merchant-facing docs that need to be in place before the v4.0 GA tag in Phase 7. Phase 5 drafts them; Phase 7 makes final brand-specific edits as part of the GA cutover. The split is intentional — bulk of writing happens here (Phase 5), short brand-section updates happen at cutover (Phase 7).

## Scope

- `documentation/migration-from-v3.md` — what changed structurally between v3.x and v4.x:
  - PHP floor bump (5.3 → 8.1) — what merchants need to do.
  - Composer package name change (still pointing at v3 name during dev; the change ships in Phase 7).
  - Namespace stays `angelleye\PayPal` — no `use` statement changes.
  - Telemetry removed.
  - Full REST surface added.
  - How to upgrade in three steps: bump PHP, `composer require`, optionally flip `upgrade_from_classic`.
- `documentation/brand-history.md` — Angell EYE → angellops (GitHub-only rename) → Wekoodo (v4.0+):
  - Why the PHP namespace `angelleye\PayPal` is preserved (BC).
  - Where to find the package on Packagist (post-Phase-7: `wekoodo/paypal-php-library` canonical; `angelleye/paypal-php-library` marked abandoned).
  - GitHub URL redirects.
  - Current contact channels under Wekoodo.
- `README.md` (top-level) — header with "**Formerly Angell EYE — now Wekoodo**" notice + link to brand-history; REST quickstart code snippet; upgrade walkthrough teaser; full doc index.
- `CHANGELOG.md` — v4.0.0 entry. **Order matters**: lead with the brand change, then REST modernization, then telemetry removal. Mention BC-breakage: PHP floor.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `documentation/migration-from-v3.md` | NEW | |
| `documentation/brand-history.md` | NEW | |
| `README.md` | EDIT | Header brand notice + REST quickstart + doc index |
| `CHANGELOG.md` | EDIT | v4.0.0 entry |

## Acceptance criteria

- [ ] Both new documentation files exist with the sections listed above.
- [ ] `README.md` displays the "Formerly Angell EYE — now Wekoodo" notice prominently.
- [ ] `README.md` includes a REST quickstart code snippet that works against a fresh install.
- [ ] `CHANGELOG.md` v4.0.0 entry leads with brand change, then REST modernization, then telemetry removal.
- [ ] Phase 7 will make any final brand polish — Phase 5's job is to land the bulk.
- [ ] Markdown renders cleanly on GitHub.

## References

- PRD: [§4 Phase 5](../../PRD.md#phased-rollout), [§4 Phase 7](../../PRD.md#phased-rollout)
- Memory: [Brand history & rebrand](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/project_brand_history.md)
