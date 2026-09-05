# Phase 6.1 — Manual demo verifications

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 6 · **Issue:** TBD · **PRD sections:** [§5 Manual Demo Verification](../../PRD.md#manual-demo-verification)

## Context

The formal release gate. The 5 manual demo walkthroughs from PRD §5, run end-to-end in a browser against PayPal sandbox. These are the canonical signal that v4.0 is ready: if every demo runs clean, the SDK is releasable. If any demo regresses, the issue is fixed before tagging the RC.

## Scope

Walk all 5 manual demos and record results:

1. **Classic-only demo** (`demo/classic/express-checkout-basic/`, `upgrade_from_classic = false`) — confirm baseline still works.
2. **Upgrade-mode demo** (same files, `upgrade_from_classic = true` + REST creds) — confirm identical buyer experience + identical response shape (TOKEN, REDIRECTURL, PAYMENTINFO_0_TRANSACTIONID, ACK=Success). The PayPal-hosted approval URL legitimately changes from `cgi-bin/webscr` to `checkoutnow` — that's expected.
3. **REST Smart Buttons demo** (`demo/rest/checkout-standard/`) — confirm JS SDK loads, Smart Buttons render, completing checkout returns to merchant page with capture details.
4. **REST redirect demo** (`demo/rest/checkout-redirect/`) — server-only flow.
5. **Upgrade-check CLI** — `vendor/bin/paypal-upgrade-check tests/Fixtures/sample-merchant-codebases/` — verify classification output across all 4 buckets.

If any demo fails or shows a regression, the issue is opened, fixed (in a new plan file or as part of an existing one), and Phase 6 restarts.

## Files affected

| Path | Action | Notes |
|---|---|---|
| (none — read-only verification) | — | This plan walks the demos and reports |

## Acceptance criteria

- [ ] All 5 demo walkthroughs pass end-to-end against sandbox.
- [ ] Demo #2 produces identical response shape to demo #1 (diffed, excluding inherently-per-call fields like CORRELATIONID and TIMESTAMP).
- [ ] No critical or blocker-level regressions surface.
- [ ] Findings document is written summarizing results (one row per demo, pass/fail, notes).

## Verification

```bash
# Local web server for the demos
php -S localhost:8000 -t demo/

# Then manually walk each demo in the browser. Record results.
```

## References

- PRD: [§5 Manual Demo Verification](../../PRD.md#manual-demo-verification)
- Upstream: all of Phase 0-4 (demos must work end-to-end)
- Downstream: [`02-rc-tag-and-bake.md`](02-rc-tag-and-bake.md) — gated on this passing
