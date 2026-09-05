# Phase 2.7 — Resources\CatalogProducts

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/CatalogProducts](../../PRD.md#proposed-file-structure)

## Context

Catalog products (`/v1/catalogs/products`) are the parent of Plans. Most merchants set up one product per "thing they sell on a recurring basis" (e.g., "Monthly subscription"). Required for the Subscriptions trio to work.

## Scope

- `Resources\CatalogProducts` exposing:
  - `create(array $body, ?RequestOptions $opts = null): ProductResponse`
  - `list(?int $page = null, ?int $pageSize = null): array<ProductResponse>`
  - `show(string $productId): ProductResponse`
  - `update(string $productId, array $patches): void`
- `Responses\ProductResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/CatalogProducts.php` | NEW | |
| `src/REST/Responses/ProductResponse.php` | NEW | |
| `tests/Unit/REST/Resources/CatalogProductsTest.php` | NEW | |
| `tests/Integration/REST/CatalogProductsHappyPathTest.php` | NEW | Sandbox-gated |
| `tests/Fixtures/responses/catalog-products-*.json` | NEW | |
| `documentation/rest/catalog-products.md` | NEW | |

## Acceptance criteria

- [ ] All 4 methods work against mocked + sandbox responses.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/catalog-products/v1/
