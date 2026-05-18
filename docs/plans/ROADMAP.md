# v4.0 Roadmap

Source of truth for phases and iterations. GitHub milestone (`v4.0`) and issues mirror this file — when they disagree, **this file wins** and the next agent updates GitHub to match.

> See [docs/plans/README.md](README.md) for the workflow these phases and iterations plug into.

- **Base branch for iteration work:** `feat/219-ppcp-integration` (NOT `release`)
- **Vision & scope:** [docs/PRD.md](../PRD.md)
- **GitHub milestone:** [v4.0](https://github.com/angellops/paypal-php-library/milestones)
- **Recommended starting iteration:** **0.1 — orphan-vendor-cleanup** (zero dependencies, lowest-risk deletions, smallest iteration in the roadmap — a good calibration session before the rest).

---

## Status legend

- 🔵 not started
- 🟡 in progress
- ✅ done
- ⚪ deferred / on hold

---

## Phases

| # | Name | Goal | Milestone | Status |
|---|---|---|---|---|
| 0 | Cleanup & Foundation | Strip the old REST stack + AWS tracker; raise PHP floor to `^8.1`; lay PSR-4 autoload + PHPUnit; introduce `Support\PartnerAttribution` constant + `Support\Reference` extraction. | `v4.0` | 🔵 |
| 1 | Core REST Plumbing | Value objects, transport, auth, TokenStore drivers, `REST\Client` facade — no PayPal-resource-specific code yet. | `v4.0` | 🔵 |
| 2 | REST Resource Handlers | 13 handlers + DTOs + unit + sandbox happy-path tests + per-resource doc pages (`documentation/rest/{resource}.md`). | `v4.0` | 🔵 |
| 3 | Legacy Adapter & Upgrade Path | `Legacy\RESTBackend` + EC-token bridge + 28 mappers + dispatch-hook wiring in `PayPal.php` + `paypal-upgrade-check` CLI. | `v4.0` | 🔵 |
| 4 | Templates / Samples / Demos / Helper | REST templates + samples paralleling Classic structure; `demo/rest/checkout-standard` + `demo/rest/checkout-redirect`; `Support\ButtonHelper`; config-sample updates. | `v4.0` | 🔵 |
| 5 | Documentation | Cross-cutting docs only (per-resource docs ship with Phase 2): upgrade-from-classic walkthrough, REST overview, JS SDK guide, webhooks operational guide, migration-from-v3, brand history. | `v4.0` | 🔵 |
| 6 | Brand & Release | E2E demo verification (release gate); in-repo brand transition (composer rename, README, CHANGELOG, in-code string replacements); RC tag → 1-week bake → GA tag → out-of-band coordination. | `v4.0` | 🔵 |

---

## Iterations

> One row per iteration. PR closes 1 issue. Iteration MD is the executable spec.

### Phase 0: Cleanup & Foundation

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 0.1 | orphan-vendor-cleanup | Delete the 7 orphaned legacy REST classes + `PayPalREST.php` + `vendor/paypal/rest-api-sdk-php/`; regenerate `composer.lock`. | #239 | — | — | 🔵 |
| 0.2 | remove-aws-telemetry | Strip the AWS endpoint URL, `TPV_Parse_Request` / `TPV_Send_Request`, and their call sites from `PayPal.php`. | #240 | — | — | 🔵 |
| 0.3 | composer-modernization | PHP floor `^8.1`; declare PSR-4 alongside PSR-0 for `angelleye\PayPal\{REST,Legacy,Support}\`; add `psr/log` + `psr/simple-cache`; suggest `guzzlehttp/guzzle`. | #241 | — | — | 🔵 |
| 0.4 | phpunit-scaffolding | `phpunit.xml`, `tests/Unit` + `tests/Integration` + `tests/Fixtures` tree; first smoke test verifying `PayPal.php` still loads cleanly. | #242 | — | — | 🔵 |
| 0.5 | partner-attribution-bn-code | New `Support\PartnerAttribution::VALUE = 'WekoodoLLC_Ecom'` constant; replace `AngellEYELLC_Ecom_PHPCatalog` literals in `PayPal.php`. | #243 | — | — | 🔵 |
| 0.6 | reference-extraction | Extract `PayPal.php:119–489` (Countries / States / AVS / CVV2 / Currencies) to `Support\Reference`; preserve existing public behavior. | #244 | — | — | 🔵 |

### Phase 1: Core REST Plumbing

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 1.1 | rest-foundation | `REST\Config` + the 12 `REST\Exceptions\*` classes + `REST\Http\{Request, Response, RequestOptions, Prefer, TransportInterface}` + `REST\Auth\{AccessToken, AuthAssertionBuilder}` + `REST\TokenStore\TokenStoreInterface`. All immutable / interface-only. | #245 | — | — | 🔵 |
| 1.2 | curl-transport | Zero-dep cURL implementation: SSL verification on by default, error → exception mapping, `debug_id` surfacing, headers (Bearer + `PayPal-Request-Id` + `Partner-Attribution-Id`). | #246 | — | — | 🔵 |
| 1.3 | tokenstore-drivers | `InMemoryTokenStore` (default), `FilesystemTokenStore` (0600 perms + world-readable refusal + file locking), `Psr16TokenStore`; concurrency test simulating 10 forked workers asserting single-flight refresh. | #247 | — | — | 🔵 |
| 1.4 | oauth2-client-facade | `REST\Auth\OAuth2Authenticator` (client_credentials, single-flight refresh, jittered backoff) + `REST\Resources\BaseResource` + `REST\Responses\BaseResponse` + `REST\Client` (lazy accessors for 13 Phase 2 resources). | #248 | — | — | 🔵 |

### Phase 2: REST Resource Handlers

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 2.1 | rest-orders | `REST\Resources\Orders` + `OrderResponse` / `CaptureResponse` / `AuthorizationResponse` DTOs; sandbox path: create → capture. Foundational — every other resource's fixtures reference Order/Capture/Authorization. | #249 | — | — | 🔵 |
| 2.2 | rest-payments | `REST\Resources\Payments` + refund/authorize/capture DTOs; sandbox path: authorize → capture → refund (reuses 2.1 fixtures). | #250 | — | — | 🔵 |
| 2.3 | rest-webhooks | `REST\Resources\Webhooks` (CRUD on subscriptions + event types) and `REST\Resources\WebhookVerifier` (`/verify-webhook-signature`); doc covers both. | #251 | — | — | 🔵 |
| 2.4 | rest-plans-catalog | `REST\Resources\Plans` + `REST\Resources\CatalogProducts` — the two subscription prerequisites. | #252 | — | — | 🔵 |
| 2.5 | rest-subscriptions | `REST\Resources\Subscriptions` (full lifecycle: create / get / update / suspend / activate / cancel / capture); **depends on 2.4's handoff**. | #253 | — | — | 🔵 |
| 2.6 | rest-invoicing | `REST\Resources\Invoicing` covering invoices + templates + QR code. | #254 | — | — | 🔵 |
| 2.7 | rest-payouts | `REST\Resources\Payouts` (batch + items + unclaimed). | #255 | — | — | 🔵 |
| 2.8 | rest-disputes | `REST\Resources\Disputes` (list, get, provide evidence); tested against 2.1/2.2 fixtures. | #256 | — | — | 🔵 |
| 2.9 | rest-vault | `REST\Resources\Vault` (payment-tokens + setup-tokens v3). | #257 | — | — | 🔵 |
| 2.10 | rest-identity-reports-referrals | Three smallest handlers in one iteration: `PartnerReferrals` + `Identity` + `Reports`. | #258 | — | — | 🔵 |

### Phase 3: Legacy Adapter & Upgrade Path

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 3.1 | legacy-adapter-infra | `Legacy\RESTBackend` dispatcher + `EcTokenBridge` + `ResponseShaper` + `ErrorTranslator` + `UnmappableMethods` + `FieldMap` (~200 leaf mappings) + `UnmappableMethodException` / `LegacyConfigException`. No mappers yet. | #259 | — | — | 🔵 |
| 3.2 | mappers-express-checkout | `SetExpressCheckout`, `GetExpressCheckoutDetails`, `DoExpressCheckoutPayment`, `DoDirectPayment`, `DoReferenceTransaction` mappers (~5) + dispatch hooks; integration tests cover the EC-token bridge end-to-end. | #260 | — | — | 🔵 |
| 3.3 | mappers-payment-lifecycle | `DoAuthorization`, `DoCapture`, `DoReauthorization`, `DoVoid`, `RefundTransaction`, `ManagePendingTransactionStatus` mappers (~6) + dispatch hooks. | #261 | — | — | 🔵 |
| 3.4 | mappers-transaction-queries | `GetTransactionDetails`, `TransactionSearch`, `MassPay` (→ Payouts), `GetBalance` (→ Reports) mappers (~4) + dispatch hooks. | #262 | — | — | 🔵 |
| 3.5 | mappers-recurring-billing | 6 Recurring Profile mappers + 2 Billing Agreement mappers (~8 total) + dispatch hooks. Largest mapper iteration; `CreateRecurringPaymentsProfile` orchestrates Plans + Subscription. | #263 | — | — | 🔵 |
| 3.6 | mappers-invoicing | `CreateInvoice`, `SendInvoice`, `GetInvoiceDetails`, `UpdateInvoice`, `CancelInvoice` mappers (~5) + dispatch hooks. | #264 | — | — | 🔵 |
| 3.7 | upgrade-check-cli | `bin/paypal-upgrade-check` using `nikic/php-parser` (dev dep); scans a merchant codebase and classifies Classic method calls as cleanly-upgradable / upgradable-with-caveats / unmappable. | #265 | — | — | 🔵 |

### Phase 4: Templates / Samples / Demos / Helper

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 4.1 | rest-templates | 32 blank shells under `templates/rest/`, paralleling `templates/classic/`. | #266 | — | — | 🔵 |
| 4.2 | rest-samples | 32 populated runnable samples under `samples/rest/`, paralleling `samples/classic/`. | #267 | — | — | 🔵 |
| 4.3 | demo-rest-checkout-standard | Modern Smart Buttons demo (5 files: `index.php` with JS SDK + `create-order.php` XHR + `capture-order.php` XHR + `order-complete.php` + shared `assets/`) plus `Support\ButtonHelper`. | #268 | — | — | 🔵 |
| 4.4 | demo-rest-checkout-redirect | Server-only redirect demo (6 files mirroring `demo/classic/express-checkout-basic/`) + `samples/config/config-sample.php` updated with all new config keys. | #269 | — | — | 🔵 |

### Phase 5: Documentation

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 5.1 | docs-upgrade-from-classic | `documentation/upgrade-from-classic.md` — the full 5-step migration: mapping table for the 28 mapped Classic methods, unmappable list, EC-token bridge explanation, `classic_methods_passthrough` usage. | #270 | — | — | 🔵 |
| 5.2 | docs-rest-overview-jssdk | `documentation/rest/index.md` (architecture overview + getting-started) + `documentation/js-sdk-upgrade-guide.md` (Smart Buttons UX modernization). | #271 | — | — | 🔵 |
| 5.3 | docs-webhooks-migration-history | `documentation/webhooks.md` (operational guide) + `documentation/migration-from-v3.md` + `documentation/brand-history.md` (Angell EYE → angellops → Wekoodo). | #272 | — | — | 🔵 |

### Phase 6: Brand & Release

| # | Slug | Goal (1 line) | Issue(s) | Branch | PR | Status |
|---|---|---|---|---|---|---|
| 6.1 | e2e-verification | Run all demo kits in sandbox (Classic, upgrade mode, REST Smart Buttons, REST redirect); full PHPUnit suite at ≥80% line coverage on `REST/` / `Legacy/` / `Support/`; `paypal-upgrade-check` against representative codebases; fix regressions. | #273 | — | — | 🔵 |
| 6.2 | brand-transition | `composer.json` rename (`name` → `wekoodo/paypal-php-library`, Wekoodo URLs, authors block) + `README.md` Wekoodo notice + `CHANGELOG.md` v4.0.0 entry + in-code "Angell EYE" → "Wekoodo" replacements in user-facing strings only (namespace `angelleye` preserved). | #274 | — | — | 🔵 |
| 6.3 | release-ceremony | Tag `v4.0.0-rc1`; 1-week RC bake with demo kits as validation gate; tag `v4.0.0` GA; close v4.0 milestone; coordinate out-of-band actions (GitHub org transfer, Packagist publish, abandoned flag). | #275 | — | — | 🔵 |

---

## Out-of-band items (maintainer actions, not iterations)

Tracked on the v4.0 milestone with the `out-of-band` label. These are not coded iterations — they're maintainer actions that have to land alongside the `v4.0.0` tag. Six items from the PRD's "Out-of-Band Items" section:

| # | Item | Coordinates with | Issue | Status |
|---|---|---|---|---|
| OOB-1 | AWS endpoint decommission confirmation (already done per maintainer) | — | #276 | ✅ |
| OOB-2 | GitHub org transfer `angellops` → `Wekoodo` | iteration 6.3 | #277 | 🔵 |
| OOB-3 | Packagist publish of `wekoodo/paypal-php-library` (with auto-tag webhook) | iteration 6.3 | #278 | 🔵 |
| OOB-4 | Mark `angelleye/paypal-php-library` as `"abandoned": "wekoodo/paypal-php-library"` on Packagist | iteration 6.3 (after publish) | #279 | 🔵 |
| OOB-5 | (Optional) Recruit 1–2 beta merchants for the 1-week RC bake | iteration 6.3 (RC bake window) | #280 | 🔵 |
| OOB-6 | v3.x deprecation notice + social/brand asset updates | iteration 6.3 | #281 | 🔵 |

---

## Maintenance notes

- **After each iteration is merged:** flip the row's Status to ✅, fill in the PR column with the merged PR URL.
- **When iteration work starts:** flip Status to 🟡, fill in the Branch column.
- **If an iteration splits during iteration-spec planning** (e.g., 3.5 ends up being two PRs): renumber sub-rows as `3.5a` / `3.5b` and update both this table and the GitHub milestone.
- **The Issue column was filled in** when the v4.0 milestone's stub issues were created in Stage 4 of the roadmap-planning session (issues #239–#281). It doesn't change after that.

---

## Change log

- **2026-05-16** — Initial roadmap population. 37 iterations across 7 phases sized to 1–2 SP each. Decisions baked in: delete `PayPalREST.php` (no carry-forward), brand transition deferred to Phase 6, PHPUnit (not Pest), dual PSR-0/PSR-4 (full migration deferred to v5.0), each Phase 3 mapper iteration wires its own dispatch hooks, 1-week RC bake with demo kits as the validation gate.
