# Phase 3.2 — EcTokenBridge

**Phase:** 3 · **Issue:** TBD · **PRD sections:** [§3 Legacy/EcTokenBridge](../../PRD.md#proposed-file-structure), [§4 Risks / EC-token bridge fragility](../../PRD.md#technical-risks)

## Context

Classic Express Checkout returns an `EC-XXXXXXXXX` token that the merchant then redirects the buyer to `paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-...`. REST's equivalent is `paypal.com/checkoutnow?token=ORDER_ID`. The EC-XXX token shape is wired into many merchants' callback URLs, return URL handlers, and database schemas. The EcTokenBridge issues synthetic EC-XXX tokens, maps them to REST order_ids, and stores the mapping. **Critically, the lookup accepts BOTH forms** (synthetic EC-XXX OR raw REST order_id) so merchants whose framework strips/rewrites query params still resolve correctly (per PRD risk mitigation).

## Scope

- `Legacy\EcTokenBridge` exposing:
  - `mintToken(string $orderId): string` — issues a fresh `EC-` prefixed token (17 chars after the `EC-`), stores `EC-XXX ↔ $orderId` mapping in the configured TokenStore.
  - `resolveToOrderId(string $tokenOrOrderId): string` — if input matches `EC-` shape, look up mapping; if input matches REST order_id shape, return as-is. Throws `LegacyConfigException` if input is neither and no mapping exists.
  - `invalidate(string $token): void` — clear the mapping after order completion.
- TokenStore backends supported via injection: session (default for upgrade-mode), filesystem, PSR-16 (Redis/Memcached/database). The Bridge uses whichever the merchant's `Config` declared.
- Synthetic token format: `EC-` followed by 17 base32 chars derived from the order_id + random suffix to avoid collisions. Specific scheme documented in code comments.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/Legacy/EcTokenBridge.php` | NEW | |
| `tests/Unit/Legacy/EcTokenBridgeTest.php` | NEW | Mint, resolve (both forms), invalidate, collision-resistance |
| `tests/Integration/Legacy/EcTokenBridgeBackendsTest.php` | NEW | Tests each TokenStore backend |

## Acceptance criteria

- [ ] `mintToken('5O190127TN364715T')` returns a string matching `/^EC-[A-Z0-9]{17}$/`.
- [ ] `resolveToOrderId($mintedToken)` returns the original order_id.
- [ ] `resolveToOrderId($rawOrderId)` returns the order_id as-is (accepts both forms).
- [ ] `invalidate($token)` removes the mapping; subsequent `resolveToOrderId($token)` throws.
- [ ] Token collisions across 100,000 mints: zero (probability bound met).
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter EcTokenBridge
```

## References

- PRD: [§4 Risks / EC-token bridge fragility](../../PRD.md#technical-risks)
- Upstream: [`01-legacy-foundation.md`](01-legacy-foundation.md), Phase 1 TokenStore
- Downstream: [`03-express-checkout-mappers.md`](03-express-checkout-mappers.md) (mints tokens), [`11-paypal-php-dispatch-hook.md`](11-paypal-php-dispatch-hook.md) (passes token through to merchant return URLs)
