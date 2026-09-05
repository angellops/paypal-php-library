# Phase 0.3 — composer.json modernization & PSR-4 autoload

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 0 · **Issue:** TBD · **PRD sections:** [§3 Integration Points / Composer](../../PRD.md#integration-points), [§4 Phase 0](../../PRD.md#phased-rollout)

## Context

`composer.json` on `main` declares a PHP 5.3.0 floor, declares no PSR-4 autoload paths, has no `psr/log` or `psr/simple-cache` dependencies, and exposes no `scripts` block. v4.0 needs a PHP 8.1+ floor, PSR-4 autoload for the new `REST/`, `Legacy/`, and `Support/` namespaces (while keeping PSR-0 for the legacy `angelleye\PayPal` classes), the two PSR interface libraries we depend on, a suggested Guzzle path, a dev dep on `nikic/php-parser` for the upgrade-check CLI, and `composer test` / `composer phpstan` convenience scripts. After Phase 0.3, `composer install` cleanly installs everything v4.0 needs without the vendor SDK and with the new autoload paths registered.

## Scope

- Bump `composer.json` `require` PHP constraint from `>=5.3.0` to `^8.1`.
- Add PSR-4 autoload entries for `angelleye\\PayPal\\REST\\` → `src/REST/`, `angelleye\\PayPal\\Legacy\\` → `src/Legacy/`, `angelleye\\PayPal\\Support\\` → `src/Support/`.
- Keep the existing PSR-0 declaration for `angelleye\\PayPal` (legacy `PayPal`, `PayFlow`, `Adaptive`, `Financing` classes stay at `src/angelleye/PayPal/`).
- Add `psr/log` and `psr/simple-cache` to `require`.
- Add `guzzlehttp/guzzle` to `suggest` (optional alternative `TransportInterface` impl).
- Add `nikic/php-parser` to `require-dev` (needed by the upgrade-check CLI in Phase 3.12).
- Add `phpunit/phpunit` and `phpstan/phpstan` to `require-dev` (needed by `04-test-and-ci-setup.md`).
- Add a `scripts` block exposing `composer test` (→ `vendor/bin/phpunit`) and `composer phpstan` (→ `vendor/bin/phpstan analyse`).
- Regenerate `composer.lock` locally (it's gitignored — no commit).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `composer.json` | EDIT | `require` PHP constraint, PSR-4 autoload, deps, dev-deps, suggest, scripts |
| `autoload.php` | EDIT (if needed) | Verify the custom SPL fallback at repo root still cooperates with the composer-managed autoload for new namespaces |

## Acceptance criteria

- [ ] `composer.json` `require` block has `"php": "^8.1"`.
- [ ] `composer.json` declares PSR-4 autoload for `angelleye\\PayPal\\REST\\`, `Legacy\\`, `Support\\` at `src/REST/`, `src/Legacy/`, `src/Support/` respectively.
- [ ] `composer.json` keeps PSR-0 for `angelleye\\PayPal` pointing at `src/`.
- [ ] `composer.json` `require` includes `psr/log` and `psr/simple-cache`.
- [ ] `composer.json` `require-dev` includes `phpunit/phpunit`, `phpstan/phpstan`, `nikic/php-parser`.
- [ ] `composer.json` `suggest` includes `guzzlehttp/guzzle`.
- [ ] `composer.json` `scripts` exposes `composer test` and `composer phpstan`.
- [ ] `composer validate --strict` passes.
- [ ] `composer install` on a clean checkout succeeds without warnings.
- [ ] `composer dump-autoload -o` reports both PSR-0 and PSR-4 entries for the `angelleye\PayPal` family.
- [ ] A minimal `Support\PartnerAttribution::VALUE` access via the autoloader works.

## Verification

```bash
composer validate --strict
composer install
composer dump-autoload -o

php -r 'require "vendor/autoload.php";
echo \angelleye\PayPal\Support\PartnerAttribution::VALUE . "\n";
new \angelleye\PayPal\PayPal([]);  // PSR-0 path still works
echo "OK\n";'
```

## References

- PRD: [§4 Phase 0 composer bullets](../../PRD.md#phased-rollout)
- Upstream plans: [`02-paypal-php-modernization.md`](02-paypal-php-modernization.md) (creates the `Support\\` classes that this plan autoloads)
- Downstream plans: [`04-test-and-ci-setup.md`](04-test-and-ci-setup.md), all of Phase 1
