# Phase 4.3 — demo/rest/checkout-standard/ (Smart Buttons)

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§2.1 AC2.4](../../PRD.md#user-stories--acceptance-criteria), [§5 Manual Demo Verification](../../PRD.md#manual-demo-verification)

## Context

The canonical modern REST demo kit. Demonstrates Smart Buttons via JS SDK: front-end renders Smart Buttons, `createOrder` callback POSTs to `create-order.php` which creates a REST order, `onApprove` callback POSTs to `capture-order.php` which captures, redirects to `order-complete.php`. This is one of three release-gate demo kits that Phase 6 walks end-to-end.

## Scope

- 5 PHP files under `demo/rest/checkout-standard/`:
  - `index.php` — HTML page with `Support\ButtonHelper::renderSmartButtons()` emitting the JS SDK script tag + Smart Buttons mount point. JS code defines `createOrder` (XHR POST to `create-order.php`) and `onApprove` (XHR POST to `capture-order.php` then redirect to `order-complete.php`).
  - `create-order.php` — Server-side: builds an Orders body from query params or hardcoded test cart, calls `$client->orders->create()`, returns JSON `{id: '<order_id>'}` for the JS SDK to consume.
  - `capture-order.php` — Server-side: reads `order_id` from POST, calls `$client->orders->capture()`, stashes the capture response in session, returns JSON.
  - `order-complete.php` — Reads stashed capture from session, renders a confirmation page with the capture id and amount.
  - `assets/cart-data.php` — Shared test cart data so all three pages have the same example items.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `demo/rest/checkout-standard/index.php` | NEW | JS SDK Smart Buttons via ButtonHelper |
| `demo/rest/checkout-standard/create-order.php` | NEW | XHR endpoint |
| `demo/rest/checkout-standard/capture-order.php` | NEW | XHR endpoint |
| `demo/rest/checkout-standard/order-complete.php` | NEW | Confirmation page |
| `demo/rest/checkout-standard/assets/cart-data.php` | NEW | Shared cart fixture |

## Acceptance criteria

- [ ] Visiting `http://localhost/demo/rest/checkout-standard/index.php` renders the page with Smart Buttons mounted.
- [ ] Clicking the PayPal Smart Button opens PayPal's in-modal checkout.
- [ ] Completing checkout returns the buyer to `order-complete.php` showing the capture id and amount.
- [ ] The full flow uses zero `paypal/rest-api-sdk-php` code (only the new `REST\Client`).
- [ ] All 5 files are syntactically valid PHP.

## Verification

Manual: walk the full flow in a browser against sandbox.

```bash
find demo/rest/checkout-standard -name '*.php' -exec php -l {} \;
```

## References

- PRD: [§5 Manual Demo Verification #3](../../PRD.md#manual-demo-verification)
- Upstream: [`05-support-button-helper.md`](05-support-button-helper.md), Phase 2 Orders
