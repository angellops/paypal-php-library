# Phase 5.1 — documentation/upgrade-from-classic.md

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 5 · **Issue:** TBD · **PRD sections:** [§4 Phase 5](../../PRD.md#phased-rollout), [§2.3 User Flow](../../PRD.md#user-flow--the-upgrade-experience)

## Context

The single most important documentation deliverable. Merchants on Classic NVP integrations land here when they're ready to flip the upgrade switch. Walks the full 5-step upgrade flow, tables the 30 mapped methods, lists the auto-fallback methods (route to Classic NVP if Classic creds are present), lists the truly-unmappable methods (Adaptive / Permissions / Hosted Buttons orphans) with PayPal-side alternatives, and documents the known behavioral differences.

## Scope

- New file `documentation/upgrade-from-classic.md`. Sections:
  1. **Before you start** — prerequisites: PHP 8.1, REST credentials at developer.paypal.com, kept Classic credentials.
  2. **Step 1: Add REST credentials to your config**.
  3. **Step 2: Run the upgrade-check CLI** — link to CLI usage, sample output.
  4. **Step 3: Flip `upgrade_from_classic = true`** — and only that. No `classic_methods_passthrough`.
  5. **Step 4: Test in sandbox** — running existing demos, looking for the redirect URL change.
  6. **Step 5: Promote to production**.
  7. **The 30 mapped methods** — table: Classic method → REST endpoint → known caveats. Link to per-method PRD section if details warrant.
  8. **Auto-fallback methods** — table of methods that route to Classic NVP automatically when Classic creds are present (BMCreateButton, DoNonReferencedCredit, etc.).
  9. **Truly unmappable methods** — table: Classic method → "no REST equivalent, must rewrite". With PayPal-side alternatives.
  10. **Behavioral differences** — MassPay → Payouts async batching, recurring orchestration, EC token format change, dispute lifecycle timings.
  11. **Troubleshooting** — common errors and how to debug them; how to read the PSR-3 NOTICE log line for auto-fallback methods.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `documentation/upgrade-from-classic.md` | NEW | The big upgrade doc |

## Acceptance criteria

- [ ] Document has all 11 sections listed above.
- [ ] Method tables list all 30 mappers + auto-fallback methods + a representative set of unmappable methods.
- [ ] At least 3 behavioral differences documented with specific examples.
- [ ] Markdown renders cleanly on GitHub.

## Verification

```bash
# Open the file in a markdown previewer or push and view on GitHub.
wc -l documentation/upgrade-from-classic.md  # Expect a substantial doc, likely 300-500 lines
```

## References

- PRD: [§2.3 User Flow](../../PRD.md#user-flow--the-upgrade-experience), [§4 Risks / Mapper drift](../../PRD.md#technical-risks)
- Upstream: all of Phase 3
