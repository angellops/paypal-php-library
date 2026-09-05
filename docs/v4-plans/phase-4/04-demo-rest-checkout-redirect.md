# Phase 4.4 — demo/rest/checkout-redirect/ (server-only)

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§2.1 AC2.5](../../PRD.md#user-stories--acceptance-criteria), [§5 Manual Demo Verification](../../PRD.md#manual-demo-verification)

## Context

Server-only redirect demo: no JS SDK, no Smart Buttons. Merchant clicks "Pay with PayPal" → server creates a REST order, redirects buyer to PayPal's approval URL, PayPal redirects back to merchant's return URL with order_id, server captures and shows confirmation. Mirrors the file shape of `demo/classic/express-checkout-basic/` so merchants can directly compare.

## Scope

- 6 PHP files under `demo/rest/checkout-redirect/`:
  - `index.php` — Cart page with "Pay with PayPal" form button.
  - `CreateOrder.php` — Server-side: builds Orders body, calls `$client->orders->create()`, redirects to PayPal's approval URL.
  - `OrderReturn.php` — Buyer's return-URL landing page. Receives `order_id` query param.
  - `CaptureOrder.php` — Capture endpoint called from `OrderReturn.php` after buyer confirms.
  - `OrderComplete.php` — Confirmation page.
  - `OrderCancel.php` — Cancel-URL landing page.

The naming follows the Classic Express Checkout demo's UpperCamelCase per-file pattern.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `demo/rest/checkout-redirect/index.php` | NEW | Cart |
| `demo/rest/checkout-redirect/CreateOrder.php` | NEW | Creates + redirects |
| `demo/rest/checkout-redirect/OrderReturn.php` | NEW | Return URL handler |
| `demo/rest/checkout-redirect/CaptureOrder.php` | NEW | Capture |
| `demo/rest/checkout-redirect/OrderComplete.php` | NEW | Confirmation |
| `demo/rest/checkout-redirect/OrderCancel.php` | NEW | Cancel handler |

## Acceptance criteria

- [ ] Full server-only flow works end-to-end against sandbox.
- [ ] No JS SDK is loaded by any page in this demo.
- [ ] File names mirror `demo/classic/express-checkout-basic/` naming style.
- [ ] All 6 files are syntactically valid PHP.

## Verification

Manual: walk the full flow in a browser against sandbox.

```bash
find demo/rest/checkout-redirect -name '*.php' -exec php -l {} \;
```

## References

- PRD: [§5 Manual Demo Verification #4](../../PRD.md#manual-demo-verification)
- Upstream: Phase 2 Orders
