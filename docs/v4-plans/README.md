# v4.0 plan-file index

Plan/handoff files for the v4.0 modernization rollout. Each file is one focused session's scope of work. Each maps 1:1 to a GitHub issue under the [`v4.0` milestone](https://github.com/angellops/paypal-php-library/milestone/1).

**Source of truth:** [`docs/PRD.md`](../PRD.md). When in doubt, the PRD wins; plan files defer to it.

**How to use these files.** Fresh iteration sessions pick a plan file, read the PRD sections it references, then execute. Each plan file follows the same structured template (Context → Scope → Files affected → Acceptance criteria → Verification → References) so sessions pick up cold. When work surfaces follow-ups, open new GitHub issues using the [`v4-plan-handoff` template](../../.github/ISSUE_TEMPLATE/v4-plan-handoff.md) so the rolling list stays uniform.

**Issue links** below are filled in after the GitHub issue-creation script runs.

---

## Phase 0 — Cleanup & Foundation (Week 1, 5 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-0/01-clean-dead-code.md`](phase-0/01-clean-dead-code.md) | TBD | Delete 7 orphan REST wrapper classes, the `src/angelleye/PayPal/rest/` subtree, the vendor SDK from `composer.json` require, and the AWS telemetry from `PayPal.php`. |
| [`phase-0/02-paypal-php-modernization.md`](phase-0/02-paypal-php-modernization.md) | TBD | Create `Support\PartnerAttribution::VALUE`, wire into `PayPal.php`'s `$APIButtonSource`, extract Countries/States/AVS/CVV2/Currencies to `Support\Reference`. |
| [`phase-0/03-composer-and-autoload.md`](phase-0/03-composer-and-autoload.md) | TBD | PHP floor `^8.1`, PSR-4 autoload for new namespaces (keep PSR-0 for legacy), `psr/log` + `psr/simple-cache`, suggest `guzzlehttp/guzzle`, require-dev `nikic/php-parser`. |
| [`phase-0/04-test-and-ci-setup.md`](phase-0/04-test-and-ci-setup.md) | TBD | `phpunit.xml` + `tests/{Unit,Integration,Fixtures}/`, `.github/workflows/ci.yml` (PHPUnit + coverage + PHPStan + sandbox-integration jobs), `phpstan.neon` at level 5. |
| [`phase-0/05-phase-0-verification.md`](phase-0/05-phase-0-verification.md) | TBD | Final Phase 0 sweep: autoload smoke test, code search for AWS tracker symbols, confirm CI green. |

## Phase 1 — Core REST Plumbing (Weeks 2-3, 6 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-1/01-config-and-exceptions.md`](phase-1/01-config-and-exceptions.md) | TBD | `REST\Config` immutable value object + full `REST\Exceptions\*` hierarchy. |
| [`phase-1/02-http-layer.md`](phase-1/02-http-layer.md) | TBD | `Http\{TransportInterface, CurlTransport, Request, Response, RequestOptions, Prefer}`. SSL verify ON by default. |
| [`phase-1/03-auth-and-tokens.md`](phase-1/03-auth-and-tokens.md) | TBD | `Auth\{OAuth2Authenticator, AccessToken, AuthAssertionBuilder}` + `TokenStore\{Interface, InMemory, Filesystem, Psr16}`. |
| [`phase-1/04-base-classes-and-facade.md`](phase-1/04-base-classes-and-facade.md) | TBD | `Responses\BaseResponse`, `Resources\BaseResource`, `REST\Client` facade with lazy resource properties. |
| [`phase-1/05-support-utilities.md`](phase-1/05-support-utilities.md) | TBD | `Support\{Logger, Json}`; integrate `Support\PartnerAttribution::VALUE` into REST request headers. |
| [`phase-1/06-phase-1-integration-test.md`](phase-1/06-phase-1-integration-test.md) | TBD | End-to-end Phase 1 sanity test: Config → Client → OAuth → mock Transport → DTO. |

## Phase 2 — REST Resource Handlers (Weeks 4-9, 14 files)

One file per resource. Each ships: resource class, typed DTO(s), unit tests with captured-response fixtures, sandbox integration test (happy path), `documentation/rest/<resource>.md` page.

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-2/01-orders.md`](phase-2/01-orders.md) | TBD | `Resources\Orders` — POST /v2/checkout/orders + show/authorize/capture/patch. |
| [`phase-2/02-payments.md`](phase-2/02-payments.md) | TBD | `Resources\Payments` — v2 authorizations/captures/refunds. |
| [`phase-2/03-webhooks.md`](phase-2/03-webhooks.md) | TBD | `Resources\Webhooks` — v1 CRUD + event types. |
| [`phase-2/04-webhook-verifier.md`](phase-2/04-webhook-verifier.md) | TBD | `Resources\WebhookVerifier` — calls verify-webhook-signature API only. |
| [`phase-2/05-subscriptions.md`](phase-2/05-subscriptions.md) | TBD | `Resources\Subscriptions` — v1/billing/subscriptions. |
| [`phase-2/06-plans.md`](phase-2/06-plans.md) | TBD | `Resources\Plans` — v1/billing/plans. |
| [`phase-2/07-catalog-products.md`](phase-2/07-catalog-products.md) | TBD | `Resources\CatalogProducts` — v1/catalogs/products. |
| [`phase-2/08-invoicing.md`](phase-2/08-invoicing.md) | TBD | `Resources\Invoicing` — v2 invoices + templates + QR code. |
| [`phase-2/09-payouts.md`](phase-2/09-payouts.md) | TBD | `Resources\Payouts` — v1 batch + items + unclaimed. |
| [`phase-2/10-disputes.md`](phase-2/10-disputes.md) | TBD | `Resources\Disputes` — v1/customer/disputes. |
| [`phase-2/11-vault.md`](phase-2/11-vault.md) | TBD | `Resources\Vault` — v3 payment-tokens + setup-tokens. |
| [`phase-2/12-partner-referrals.md`](phase-2/12-partner-referrals.md) | TBD | `Resources\PartnerReferrals` — v2/customer/partner-referrals. |
| [`phase-2/13-identity.md`](phase-2/13-identity.md) | TBD | `Resources\Identity` — v1/identity/openidconnect/userinfo, client_credentials only. |
| [`phase-2/14-reports.md`](phase-2/14-reports.md) | TBD | `Resources\Reports` — v1 transactions + balances. |

## Phase 3 — Legacy Adapter & Upgrade Path (Weeks 10-12, 13 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-3/01-legacy-foundation.md`](phase-3/01-legacy-foundation.md) | TBD | `Legacy\{RESTBackend, ResponseShaper, ErrorTranslator, UnmappableMethods, MapperInterface, FieldMap}`. |
| [`phase-3/02-ec-token-bridge.md`](phase-3/02-ec-token-bridge.md) | TBD | `Legacy\EcTokenBridge` — synthetic EC-XXX ↔ REST order_id (accepts both forms on lookup). |
| [`phase-3/03-express-checkout-mappers.md`](phase-3/03-express-checkout-mappers.md) | TBD | `SetExpressCheckout`, `DoExpressCheckoutPayment`, `GetExpressCheckoutDetails` mappers. |
| [`phase-3/04-auth-capture-mappers.md`](phase-3/04-auth-capture-mappers.md) | TBD | `DoAuthorization`, `DoCapture`, `DoReauthorization`, `DoVoid` mappers. |
| [`phase-3/05-refund-search-mappers.md`](phase-3/05-refund-search-mappers.md) | TBD | `RefundTransaction`, `GetTransactionDetails`, `TransactionSearch`, `DoReferenceTransaction` mappers. |
| [`phase-3/06-direct-mass-mappers.md`](phase-3/06-direct-mass-mappers.md) | TBD | `DoDirectPayment`, `MassPay` (→ Payouts v1) mappers. |
| [`phase-3/07-recurring-mappers.md`](phase-3/07-recurring-mappers.md) | TBD | `CreateRecurringPaymentsProfile` (Plans + Subscriptions orchestration) + 5 sibling mappers. |
| [`phase-3/08-billing-agreement-mappers.md`](phase-3/08-billing-agreement-mappers.md) | TBD | `CreateBillingAgreement` (→ Vault v3), `BillAgreementUpdate` mappers. |
| [`phase-3/09-invoicing-mappers.md`](phase-3/09-invoicing-mappers.md) | TBD | 5 invoicing mappers (Create / Send / Get / Update / Cancel). |
| [`phase-3/10-other-mappers.md`](phase-3/10-other-mappers.md) | TBD | `ManagePendingTransactionStatus`, `GetBalance` (→ Reports::balances) mappers. |
| [`phase-3/11-paypal-php-dispatch-hook.md`](phase-3/11-paypal-php-dispatch-hook.md) | TBD | Wire `private ?Legacy\RESTBackend $backend` + dispatch hook into the 30 mapped public methods on `PayPal.php`. Emit PSR-3 NOTICE on construction listing auto-fallback methods. |
| [`phase-3/12-upgrade-check-cli.md`](phase-3/12-upgrade-check-cli.md) | TBD | `bin/paypal-upgrade-check` using `nikic/php-parser` (AST-based, 4 classification buckets). |
| [`phase-3/13-legacy-exceptions.md`](phase-3/13-legacy-exceptions.md) | TBD | `Legacy\Exceptions\{UnmappableMethodException, LegacyConfigException}` + auto-fallback dispatch logic. |

## Phase 4 — Templates / Samples / Demos / Helper (Week 13, 6 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-4/01-templates-rest-wipe-rebuild.md`](phase-4/01-templates-rest-wipe-rebuild.md) | TBD | Wipe stale `templates/rest/`, regenerate ~32 blank shells aligned to new REST architecture. |
| [`phase-4/02-samples-rest-wipe-rebuild.md`](phase-4/02-samples-rest-wipe-rebuild.md) | TBD | Wipe stale `samples/rest/`, regenerate ~32 populated runnable samples. |
| [`phase-4/03-demo-rest-checkout-standard.md`](phase-4/03-demo-rest-checkout-standard.md) | TBD | `demo/rest/checkout-standard/` (5 files, JS SDK Smart Buttons via `ButtonHelper`). |
| [`phase-4/04-demo-rest-checkout-redirect.md`](phase-4/04-demo-rest-checkout-redirect.md) | TBD | `demo/rest/checkout-redirect/` (6 files mirroring `demo/classic/express-checkout-basic/`). |
| [`phase-4/05-support-button-helper.md`](phase-4/05-support-button-helper.md) | TBD | `Support\ButtonHelper::renderSmartButtons()` — emits JS SDK `<script>` tag with hardcoded `WekoodoLLC_Ecom` Partner-Attribution-Id. |
| [`phase-4/06-config-sample-update.md`](phase-4/06-config-sample-update.md) | TBD | Update `samples/config/config-sample.php` with all new config keys + inline documentation. |

## Phase 5 — Documentation (Week 14, 4 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-5/01-upgrade-from-classic-doc.md`](phase-5/01-upgrade-from-classic-doc.md) | TBD | `documentation/upgrade-from-classic.md` — 5-step upgrade walkthrough, the 30 mapped methods table, auto-fallback + unmappable lists. |
| [`phase-5/02-rest-resource-docs.md`](phase-5/02-rest-resource-docs.md) | TBD | `documentation/rest/index.md` + 14 per-resource pages. |
| [`phase-5/03-js-sdk-and-webhooks-docs.md`](phase-5/03-js-sdk-and-webhooks-docs.md) | TBD | `documentation/js-sdk-upgrade-guide.md` + `documentation/webhooks.md`. |
| [`phase-5/04-readme-changelog-migration.md`](phase-5/04-readme-changelog-migration.md) | TBD | `migration-from-v3.md` + `brand-history.md` + `README.md` + `CHANGELOG.md` v4.0.0 entry. |

## Phase 6 — Verification & RC Bake (Week 15, 2 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-6/01-manual-demo-verifications.md`](phase-6/01-manual-demo-verifications.md) | TBD | The 5 manual demo walkthroughs from PRD §5. |
| [`phase-6/02-rc-tag-and-bake.md`](phase-6/02-rc-tag-and-bake.md) | TBD | Pre-release checklist sweep, tag `v4.0.0-rc1`, 1-week bake. |

## Phase 7 — Release & Brand Cutover (Week 16, 2 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-7/01-ga-cutover.md`](phase-7/01-ga-cutover.md) | TBD | Coordinated GA event: composer rename + README/CHANGELOG brand updates + GitHub repo transfer + Packagist publish + tag `v4.0.0`. |
| [`phase-7/02-oob-followups.md`](phase-7/02-oob-followups.md) | TBD | Post-GA: Packagist UI abandoned flag on `angelleye/paypal-php-library`, social/brand asset updates, early-adopter issue triage. |

---

## Plan-file template

Every plan file follows the same structured template so fresh iteration sessions pick up cold:

```markdown
# Phase <N>.<NN> — <Topic>

**Phase:** <N> · **Issue:** <gh-link> · **PRD section(s):** <link>

## Context

<1-2 paragraphs: why this work, what it depends on, what depends on it>

## Scope

<bulleted deliverables>

## Files affected

| Path | Action | Notes |
|---|---|---|
| `path/to/file` | NEW / EDIT / DELETE | <one-liner> |

## Acceptance criteria

- [ ] <concrete, verifiable>

## Verification

<commands to confirm done end-to-end>

## References

- PRD: <section link>
- Upstream plans: <list>
- Downstream plans: <list>
```
