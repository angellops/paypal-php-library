# Phase 0.1 — Clean dead code

**Phase:** 0 · **Issue:** TBD · **PRD sections:** [§3 file structure](../../PRD.md#3-technical-specifications), [§4 Phase 0](../../PRD.md#phased-rollout)

## Context

`main` carries a non-trivial amount of dead code that fatal-errors on autoload as soon as merchants install the package: 7 orphan wrapper classes at the top of `src/angelleye/PayPal/` that all `use PayPal\Common\PayPalModel` from the vendor SDK; the entire `src/angelleye/PayPal/rest/` subtree (~12 API files) that those wrappers depend on; the `paypal/rest-api-sdk-php` require line in `composer.json` that pulls the abandoned vendor SDK in on every install; and an undocumented AWS telemetry hook in `PayPal.php`'s constructor that pings a decommissioned AWS endpoint on every API call. All four of these come out in this plan file. After Phase 0.1, the codebase autoloads cleanly with no vendor-SDK coupling and no outbound HTTP to non-PayPal endpoints.

## Scope

- Delete 7 top-level orphan REST wrapper classes under `src/angelleye/PayPal/`.
- Delete the entire `src/angelleye/PayPal/rest/` subtree.
- Remove `"paypal/rest-api-sdk-php": "*"` from `composer.json`'s `require` block.
- Remove the AWS telemetry tracker from `PayPal.php`: the URL string, the API key string, the `TPV_Parse_Request` and `TPV_Send_Request` method definitions, and every call site.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/angelleye/PayPal/RestClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/CheckoutOrdersClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/CustomerDisputesClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/EventTypesClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/InvoicingClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/PayPalSyncClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/ReferencedPayoutsClass.php` | DELETE | Vendor-SDK bridge facade |
| `src/angelleye/PayPal/rest/` (entire directory) | DELETE | All 12 subdirs and contents, all `use PayPal\Api\*` from vendor SDK |
| `composer.json` | EDIT | Remove `paypal/rest-api-sdk-php` line from `require` |
| `src/angelleye/PayPal/PayPal.php` | EDIT | Search-by-symbol: AWS URL `gtctgyk7fh.execute-api...`, API key `srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa`, methods `TPV_Parse_Request` / `TPV_Send_Request`, and every call site (e.g., inside `CURLRequest`). Do NOT anchor on line numbers — they drift. |

## Acceptance criteria

- [ ] All 7 orphan wrapper class files are deleted.
- [ ] `src/angelleye/PayPal/rest/` does not exist.
- [ ] `composer.json` `require` block no longer mentions `paypal/rest-api-sdk-php`.
- [ ] `composer install --no-dev` on a clean checkout produces no `vendor/paypal/rest-api-sdk-php/` directory.
- [ ] `grep -r 'gtctgyk7fh.execute-api.us-east-2.amazonaws.com' src/ samples/ demo/` returns no hits.
- [ ] `grep -r 'srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa' src/ samples/ demo/` returns no hits.
- [ ] `grep -r 'TPV_Parse_Request\|TPV_Send_Request' src/ samples/ demo/` returns no hits.
- [ ] `PayPal.php` autoloads cleanly (PHP syntax-check + a minimal `require` smoke test pass).

## Verification

```bash
composer dump-autoload --no-dev
php -r 'require "vendor/autoload.php"; new \angelleye\PayPal\PayPal([]);'
# expect no fatal, no warnings about missing PayPal\Common\* classes

ls src/angelleye/PayPal/rest 2>&1 | grep -q "No such file" && echo "OK"
ls src/angelleye/PayPal/RestClass.php 2>&1 | grep -q "No such file" && echo "OK"

grep -nR 'gtctgyk7fh\|srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa\|TPV_Parse_Request\|TPV_Send_Request' src/ samples/ demo/ || echo "OK"

grep -q 'paypal/rest-api-sdk-php' composer.json && echo "FAIL" || echo "OK"
```

## References

- PRD: [§4 Phase 0](../../PRD.md#phased-rollout) cleanup bullets, [§5 Pre-Release Checklist](../../PRD.md#pre-release-checklist) telemetry items
- Memory: [AWS telemetry decommissioned](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/project_aws_telemetry_decommissioned.md) — endpoint and key already killed AWS-side; this is code-side cleanup only
- Upstream plans: none (this is the foundation)
- Downstream plans: [`02-paypal-php-modernization.md`](02-paypal-php-modernization.md), [`03-composer-and-autoload.md`](03-composer-and-autoload.md)
