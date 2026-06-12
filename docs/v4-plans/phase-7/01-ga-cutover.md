# Phase 7.1 — GA cutover: composer rename, GitHub move, Packagist publish, v4.0.0 tag

**Phase:** 7 · **Issue:** TBD · **PRD sections:** [§4 Phase 7](../../PRD.md#phased-rollout), [§6 Out-of-Band Items](../../PRD.md#out-of-band-items-the-maintainer-must-action)

## Context

The single coordinated event that ships v4.0 to the world: composer package rename, README/CHANGELOG brand polish, GitHub repo transfer to `Wekoodo/`, Packagist publish of the new package, and the `v4.0.0` tag. The in-repo brand transition and the maintainer-side OOB actions land alongside one commit + one tag so the world sees v4.0 + Wekoodo brand as a single event.

## Scope

- **Repo edits (final brand commit):**
  - `composer.json`: `name` → `wekoodo/paypal-php-library`; `homepage` → `https://github.com/Wekoodo/paypal-php-library`; `support.source` → same; extend `authors` block with both Angell EYE (historical) and Wekoodo (current).
  - `README.md`: prominent "**Formerly published as Angell EYE — now Wekoodo.**" header notice and brand-history link.
  - `CHANGELOG.md`: finalize v4.0.0 entry leading with brand change, then REST modernization, then telemetry removal.
  - In-code `Angell EYE` → `Wekoodo` string replacements in user-facing strings (log prefixes, error messages, default `LogPath` dirname if present). **The PHP namespace `angelleye\PayPal` stays intact.** Search by symbol; do not blanket-replace.
- **Out-of-band actions (coordinated with the tag, maintainer-driven):**
  - GitHub repo transfer: `angellops/paypal-php-library` → `Wekoodo/paypal-php-library` via GitHub's one-click transfer-repository flow. Automatic redirects from the old URL are set up by GitHub.
  - Packagist: create `wekoodo/paypal-php-library` pointing at the new GitHub repo URL. Set up Packagist's GitHub webhook so future tags auto-publish.
  - Tag `v4.0.0` on `Wekoodo/paypal-php-library` after the transfer. Packagist auto-publishes via webhook.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `composer.json` | EDIT | Name + homepage + support.source + authors |
| `README.md` | EDIT | Brand notice + history link |
| `CHANGELOG.md` | EDIT | Finalize v4.0.0 entry |
| User-facing strings across `src/` | EDIT | "Angell EYE" → "Wekoodo", preserve `angelleye\PayPal` namespace |
| Git tag `v4.0.0` | NEW | On `Wekoodo/paypal-php-library` after the transfer |
| (OOB) GitHub repo settings | TRANSFER | angellops → Wekoodo |
| (OOB) Packagist | NEW | wekoodo/paypal-php-library |

## Acceptance criteria

- [ ] `composer.json` `name === "wekoodo/paypal-php-library"`.
- [ ] `README.md` displays "Formerly Angell EYE — now Wekoodo" prominently.
- [ ] No user-facing string in `src/` contains "Angell EYE" (search by symbol).
- [ ] PHP namespace `angelleye\PayPal` is unchanged.
- [ ] `CHANGELOG.md` v4.0.0 entry is final.
- [ ] GitHub repo transfer completed; `github.com/angellops/paypal-php-library` redirects to `github.com/Wekoodo/paypal-php-library`.
- [ ] Packagist shows `wekoodo/paypal-php-library` at `v4.0.0`.
- [ ] `v4.0.0` tag exists on the new repo.

## Verification

```bash
# Local checks before the transfer:
grep -n '"name":' composer.json | grep -q 'wekoodo/paypal-php-library' && echo "OK"
grep -n 'Formerly\|Wekoodo' README.md | head
! grep -rn 'Angell EYE' src/

# After the transfer:
curl -sI https://github.com/angellops/paypal-php-library | grep -i 'location: .*Wekoodo'
composer show wekoodo/paypal-php-library --all | grep '4.0.0'
```

## References

- PRD: [§4 Phase 7](../../PRD.md#phased-rollout), [§6 OOB items 2 + 3](../../PRD.md#out-of-band-items-the-maintainer-must-action)
- Memory: [Brand history & rebrand](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/project_brand_history.md)
- Upstream: [Phase 6](../phase-6/) — RC must have baked cleanly
- Downstream: [`02-oob-followups.md`](02-oob-followups.md)
