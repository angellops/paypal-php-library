# Phase 4.5 — Support\ButtonHelper

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§3 Support/ButtonHelper](../../PRD.md#proposed-file-structure)

## Context

Emits the PayPal JS SDK `<script>` tag with the merchant's `client_id` pre-filled and the hardcoded `WekoodoLLC_Ecom` Partner-Attribution-Id set via `data-partner-attribution-id`. The merchant still owns mounting the buttons and writing the `createOrder` / `onApprove` JS callbacks — this is a PHP helper, not a full button-rendering solution. Used by `demo/rest/checkout-standard/index.php` and any merchant who wants the script tag generated for them.

## Scope

- `Support\ButtonHelper::renderSmartButtons(array $options = []): string`. Accepts:
  - `clientId` (required) — usually from merchant `Config`.
  - `currency` (default 'USD').
  - `components` (default 'buttons'; can also be 'buttons,marks,funding-eligibility').
  - `fundingSources` (optional, e.g., ['paypal', 'venmo']).
  - `intent` (default 'capture').
  - `disableFunding` (optional).
  - `commit` (default 'true').
- Returns a string containing the `<script src="https://www.paypal.com/sdk/js?...">` tag with all query params URL-encoded, plus `data-partner-attribution-id="WekoodoLLC_Ecom"` attribute. The merchant injects this into their HTML head.
- Does NOT emit any `paypal.Buttons(...)` JS — that's the merchant's responsibility, documented clearly.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Support/ButtonHelper.php` | NEW | `renderSmartButtons()` method |
| `tests/Unit/Support/ButtonHelperTest.php` | NEW | Default + custom options |

## Acceptance criteria

- [ ] `ButtonHelper::renderSmartButtons(['clientId' => 'AYxxx'])` returns a string starting with `<script src="https://www.paypal.com/sdk/js?` and containing `client-id=AYxxx`, `currency=USD`, `components=buttons`, `intent=capture`.
- [ ] Output contains `data-partner-attribution-id="WekoodoLLC_Ecom"`.
- [ ] PartnerAttribution constant is sourced from `Support\PartnerAttribution::VALUE` (no literal).
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter ButtonHelper
```

## References

- PRD: [§3 Integration Points / JS SDK](../../PRD.md#integration-points), [§4 Risks / JS SDK helper false-promises](../../PRD.md#technical-risks)
- Upstream: Phase 0 `Support\PartnerAttribution`
- Downstream: [`03-demo-rest-checkout-standard.md`](03-demo-rest-checkout-standard.md)
