# Phase 7.2 — OOB followups: abandoned flag, social assets, early-adopter triage

**Phase:** 7 · **Issue:** TBD · **PRD sections:** [§4 Phase 7 / Post-GA followups](../../PRD.md#phased-rollout), [§6 Out-of-Band Items](../../PRD.md#out-of-band-items-the-maintainer-must-action)

## Context

Post-GA followups that happen after the v4.0.0 tag publishes. None of these block the release — they're cleanup + outreach. All are maintainer-driven; they're tracked in a plan file so they don't get forgotten.

## Scope

- **Packagist UI: mark `angelleye/paypal-php-library` abandoned** — log in to Packagist, navigate to the old package, set `abandoned: wekoodo/paypal-php-library`. Composer will print the standard "package abandoned, use X instead" notice on every existing merchant's next `composer update`. **No v3.0.6 release needed** (per the brainstorming decision).
- **Social and brand asset updates** (per OOB item 6 in PRD §6):
  - Twitter/X bio (if any) updated to Wekoodo.
  - Project website (if any) updated.
  - Related angellops org repos: add a README header pointing readers to Wekoodo, or transfer them too if applicable.
- **v3.x deprecation notice** (per OOB item 5 in PRD §6) — README update on the now-transferred repo (which still has the v3.x branch history) flagging v3.x as security-only for 12 months; same notice on Packagist's old-package page.
- **Early-adopter issue triage** — monitor the new `Wekoodo/paypal-php-library` issue tracker for the first 2 weeks. Triage any urgent issues; cut a v4.0.1 patch if a critical bug surfaces.

## Files affected

| Path | Action | Notes |
|---|---|---|
| (none in-repo) | — | All OOB actions on third-party platforms |

## Acceptance criteria

- [ ] `angelleye/paypal-php-library` on Packagist shows the `abandoned` flag pointing to `wekoodo/paypal-php-library`.
- [ ] On a fresh checkout, running `composer require angelleye/paypal-php-library` prints Composer's standard abandoned notice.
- [ ] Twitter/X and any project website redirect to Wekoodo (or are explicitly updated).
- [ ] Early-adopter issue tracker is monitored for 2 weeks post-GA; any reported critical bugs have a triage response within 48 hours.

## Verification

```bash
# Verify the abandoned flag:
composer show angelleye/paypal-php-library  # Should print abandoned notice
```

## References

- PRD: [§6 OOB items 4-6](../../PRD.md#out-of-band-items-the-maintainer-must-action)
- Upstream: [`01-ga-cutover.md`](01-ga-cutover.md)
