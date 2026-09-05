# Phase 5.3 — JS SDK upgrade guide + webhooks doc

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 5 · **Issue:** TBD · **PRD sections:** [§4 Phase 5](../../PRD.md#phased-rollout)

## Context

Two cross-cutting docs that don't fit into the per-resource pages: a JS SDK upgrade guide for merchants modernizing to Smart Buttons, and a webhooks doc covering the inbound-webhook receiver pattern (merchant owns the endpoint; SDK provides verification).

## Scope

- `documentation/js-sdk-upgrade-guide.md`. Sections:
  - Why Smart Buttons (Pay Later, Venmo, advanced cards all live on the JS SDK)
  - Using `Support\ButtonHelper::renderSmartButtons()` to emit the script tag
  - Wiring the JS callbacks (`createOrder`, `onApprove`) — copy from `demo/rest/checkout-standard/`
  - Frequently asked questions (PartnerAttributionId is hardcoded, how to test in sandbox, etc.)
- `documentation/webhooks.md`. Sections:
  - Webhook overview (PayPal pushes events to merchant's URL)
  - Setting up a webhook subscription via `$client->webhooks->create()`
  - Receiving webhooks (merchant owns the HTTP endpoint)
  - Verifying webhooks with `$client->webhookVerifier->verify($headers, $rawBody, $webhookId)` — full example
  - Event types reference
  - Troubleshooting (signature mismatches, retries from PayPal, etc.)

## Files affected

| Path | Action | Notes |
|---|---|---|
| `documentation/js-sdk-upgrade-guide.md` | NEW | |
| `documentation/webhooks.md` | NEW | |

## Acceptance criteria

- [ ] Both files exist with the sections listed above.
- [ ] JS SDK doc includes at least one full working example (Smart Buttons rendering + callbacks).
- [ ] Webhooks doc includes at least one full working example (verify + handle).
- [ ] Markdown renders cleanly on GitHub.

## References

- PRD: [§4 Phase 5](../../PRD.md#phased-rollout), [§3 Integration Points / JS SDK](../../PRD.md#integration-points)
- Upstream: [`05-support-button-helper.md` (Phase 4)](../phase-4/05-support-button-helper.md), Phase 2 Webhooks + WebhookVerifier
