# Phase 4.1 — templates/rest/ wipe and rebuild

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§3 file structure / templates](../../PRD.md#proposed-file-structure), [§4 Phase 4](../../PRD.md#phased-rollout)

## Context

`templates/rest/` exists on `main` with ~14 subdirectories (`billing_agreements/`, `billing_plans/`, `checkout_orders/`, etc.) all built against the abandoned vendor REST SDK. None of it is referenced by the new REST architecture. This plan wipes the directory and regenerates ~32 blank shell template files aligned to the new resource layout, named for the methods on the new `REST\Client`.

## Scope

- Delete the entire `templates/rest/` subtree.
- Regenerate as ~32 blank shells under directories matching the new architecture:
  - `templates/rest/orders/CreateOrder.php`, `CaptureOrder.php`, `AuthorizeOrder.php`, `ShowOrder.php`
  - `templates/rest/payments/CapturePayment.php`, `RefundCapture.php`, `VoidAuthorization.php`
  - `templates/rest/subscriptions/CreateSubscription.php`, `CancelSubscription.php`, etc.
  - One file per resource per major operation, matching Classic templates style (blank with method signature and TODO comments for merchant-specific logic).
- Each shell file imports `require __DIR__ . '/../../../autoload.php';` and instantiates `new \angelleye\PayPal\REST\Client($config)` with a placeholder `$config` array, then a TODO block for the merchant to fill in.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `templates/rest/` (entire existing tree) | DELETE | Stale, vendor-coupled |
| `templates/rest/orders/*.php` | NEW | ~4 files |
| `templates/rest/payments/*.php` | NEW | ~3 files |
| `templates/rest/subscriptions/*.php` | NEW | ~3 files |
| `templates/rest/plans/*.php` | NEW | ~2 files |
| `templates/rest/catalog-products/*.php` | NEW | ~2 files |
| `templates/rest/invoicing/*.php` | NEW | ~5 files |
| `templates/rest/payouts/*.php` | NEW | ~2 files |
| `templates/rest/disputes/*.php` | NEW | ~3 files |
| `templates/rest/vault/*.php` | NEW | ~3 files |
| `templates/rest/webhooks/*.php` | NEW | ~3 files |
| `templates/rest/identity/*.php` | NEW | ~1 file |
| `templates/rest/partner-referrals/*.php` | NEW | ~1 file |
| `templates/rest/reports/*.php` | NEW | ~2 files |

## Acceptance criteria

- [ ] No file under `templates/rest/` references `PayPal\Api\*` or any other vendor-SDK class.
- [ ] Every shell file is syntactically valid PHP (`php -l` passes on each).
- [ ] Every shell file follows the same structural pattern (autoload, config placeholder, client instantiation, TODO block).
- [ ] The directory structure mirrors the new REST resources.

## Verification

```bash
find templates/rest -name '*.php' -exec php -l {} \;
grep -r 'PayPal\\Api\\' templates/rest && echo "FAIL" || echo "OK"
```

## References

- PRD: [§3 file structure / templates and samples](../../PRD.md#proposed-file-structure)
- Upstream: Phase 2 resource handlers (templates reference them)
