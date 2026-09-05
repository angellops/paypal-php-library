# Phase 5.2 — REST resource documentation

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 5 · **Issue:** TBD · **PRD sections:** [§4 Phase 5](../../PRD.md#phased-rollout)

## Context

Per-resource documentation pages plus a top-level index. Most of these get drafted during Phase 2 alongside the resource handler (per each Phase 2 plan file's acceptance criteria), but this plan plan plan provides the cohesive review pass: ensures all 14 pages exist, share the same structure, are cross-linked, and that the index page navigates merchants smoothly.

## Scope

- `documentation/rest/index.md` — overview of the new REST architecture, quick-start code snippet for `new Client($config)`, link to each resource page.
- Verify per-resource pages exist (one per resource, drafted in Phase 2):
  - `documentation/rest/orders.md`, `payments.md`, `subscriptions.md`, `plans.md`, `catalog-products.md`, `invoicing.md`, `payouts.md`, `disputes.md`, `webhooks.md`, `webhook-verifier.md`, `identity.md`, `vault.md`, `partner-referrals.md`, `reports.md`.
- Standard structure per page: Overview · Quick start · Method reference (each method = section with signature, example, params, exceptions) · Error handling · Link to PayPal upstream docs.
- Cross-link: every page footers links to the index and to the upgrade-from-classic doc.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `documentation/rest/index.md` | NEW | Overview + per-resource links |
| `documentation/rest/{14 resource pages}.md` | EDIT | Standardize structure, cross-link |

## Acceptance criteria

- [ ] `documentation/rest/index.md` exists and links to all 14 resource pages.
- [ ] All 14 per-resource pages exist and follow the standard structure.
- [ ] Every resource page has at least one runnable code example.
- [ ] Markdown renders cleanly on GitHub.

## Verification

```bash
ls documentation/rest/*.md | wc -l  # Expect 15 (index + 14 resources)
```

## References

- PRD: [§4 Phase 5](../../PRD.md#phased-rollout)
- Upstream: each Phase 2 plan file (per-resource page drafted then)
