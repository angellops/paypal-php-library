# Phase 0.2 — PayPal.php modernization

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 0 · **Issue:** TBD · **PRD sections:** [§3 Integration Points](../../PRD.md#integration-points), [§4 Phase 0](../../PRD.md#phased-rollout)

## Context

Two refactors on `PayPal.php` that prepare the legacy class for v4.0's REST integration without changing any public method signatures: (1) create `Support\PartnerAttribution::VALUE = 'WekoodoLLC_Ecom'` and wire it into the constructor's `$this->APIButtonSource` assignment (currently an empty string on `main`); and (2) extract the in-class reference-data arrays (Countries / States / AVS / CVV2 / Currencies) to a new `Support\Reference` class so the same arrays are reused by REST DTOs and the upgrade-check CLI later. Both changes are surgical: the BN-code constant is a one-line wiring change; the reference extraction is mechanical (move arrays, update accessors). No public behavior changes.

## Scope

- Create `src/Support/PartnerAttribution.php` with a single `public const VALUE = 'WekoodoLLC_Ecom';`. PSR-4 autoloaded (autoload entry lands in `03-composer-and-autoload.md`).
- Wire the constant into `PayPal.php`'s constructor — change `$this->APIButtonSource = '';` to `$this->APIButtonSource = \angelleye\PayPal\Support\PartnerAttribution::VALUE;`.
- Create `src/Support/Reference.php` with static methods or class constants that expose Countries / States / AVS / CVV2 / Currencies.
- Update `PayPal.php` to load reference data from `Support\Reference` (replace the in-class `$this->Countries = [...]` etc. assignments).
- Verify every existing public method that reads from those reference arrays still returns the same shape.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Support/PartnerAttribution.php` | NEW | Single class constant `VALUE` |
| `src/Support/Reference.php` | NEW | Countries / States / AVS / CVV2 / Currencies — preserve the exact array contents from `PayPal.php` today |
| `src/angelleye/PayPal/PayPal.php` | EDIT | Constructor: wire `APIButtonSource` to the constant; replace inline reference arrays with `Reference::` calls. Search by symbol — `$this->Countries =`, `$this->States =`, etc. |

## Acceptance criteria

- [ ] `Support\PartnerAttribution::VALUE` returns the literal string `'WekoodoLLC_Ecom'`.
- [ ] `(new PayPal([]))->APIButtonSource === \angelleye\PayPal\Support\PartnerAttribution::VALUE`.
- [ ] `Support\Reference` exposes Countries, States, AVS, CVV2, Currencies via accessible methods or constants.
- [ ] Every public method on `PayPal` that previously read from `$this->Countries` etc. continues to return identical output (verified via a fixture test).
- [ ] No public method signature on `PayPal` changes.
- [ ] No new config key is added for the BN code — it stays hardcoded in the class constant.

## Verification

```bash
php -r 'require "vendor/autoload.php";
$p = new \angelleye\PayPal\PayPal([]);
assert($p->APIButtonSource === "WekoodoLLC_Ecom");
echo "OK\n";'

vendor/bin/phpunit --filter PartnerAttribution
vendor/bin/phpunit --filter Reference
```

## References

- PRD: [§3 Partner-Attribution-Id bullet](../../PRD.md#integration-points), [§4 Phase 0 BN-code bullet](../../PRD.md#phased-rollout)
- Memory: [BN code is hardcoded, not config](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/feedback_partner_attribution_id.md) — intentionally invisible to merchants
- Upstream plans: [`01-clean-dead-code.md`](01-clean-dead-code.md)
- Downstream plans: [`03-composer-and-autoload.md`](03-composer-and-autoload.md), all of Phase 1 (`Support\PartnerAttribution` referenced by REST headers)
