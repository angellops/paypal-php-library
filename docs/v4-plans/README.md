# v4.0 plan-file index

Plan/handoff files for the v4.0 modernization rollout. Each file is one focused session's scope of work. Each maps 1:1 to a GitHub issue under the [`v4.0` milestone](https://github.com/angellops/paypal-php-library/milestone/12) (52 issues, #286–#337).

**Source of truth:** [`docs/PRD.md`](../PRD.md). When in doubt, the PRD wins; plan files defer to it.

**How to use these files.** Fresh iteration sessions pick a plan file, read the PRD sections it references, then execute. Each plan file follows the same structured template (Context → Scope → Files affected → Acceptance criteria → Verification → References) so sessions pick up cold. When work surfaces follow-ups, open new GitHub issues using the [`v4-plan-handoff` template](../../.github/ISSUE_TEMPLATE/v4-plan-handoff.md) so the rolling list stays uniform.

---

## Phase 0 — Cleanup & Foundation (Week 1, 5 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-0/01-clean-dead-code.md`](phase-0/01-clean-dead-code.md) | [#286](https://github.com/angellops/paypal-php-library/issues/286) | Delete 7 orphan REST wrapper classes, the `src/angelleye/PayPal/rest/` subtree, the vendor SDK from `composer.json` require, and the AWS telemetry from `PayPal.php`. |
| [`phase-0/02-paypal-php-modernization.md`](phase-0/02-paypal-php-modernization.md) | [#287](https://github.com/angellops/paypal-php-library/issues/287) | Create `Support\PartnerAttribution::VALUE`, wire into `PayPal.php`'s `$APIButtonSource`, extract Countries/States/AVS/CVV2/Currencies to `Support\Reference`. |
| [`phase-0/03-composer-and-autoload.md`](phase-0/03-composer-and-autoload.md) | [#288](https://github.com/angellops/paypal-php-library/issues/288) | PHP floor `^8.1`, PSR-4 autoload for new namespaces (keep PSR-0 for legacy), `psr/log` + `psr/simple-cache`, suggest `guzzlehttp/guzzle`, require-dev `nikic/php-parser`. |
| [`phase-0/04-test-and-ci-setup.md`](phase-0/04-test-and-ci-setup.md) | [#289](https://github.com/angellops/paypal-php-library/issues/289) | `phpunit.xml` + `tests/{Unit,Integration,Fixtures}/`, `.github/workflows/ci.yml` (PHPUnit + coverage + PHPStan + sandbox-integration jobs), `phpstan.neon` at level 5. |
| [`phase-0/05-phase-0-verification.md`](phase-0/05-phase-0-verification.md) | [#290](https://github.com/angellops/paypal-php-library/issues/290) | Final Phase 0 sweep: autoload smoke test, code search for AWS tracker symbols, confirm CI green. |

## Phase 1 — Core REST Plumbing (Weeks 2-3, 6 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-1/01-config-and-exceptions.md`](phase-1/01-config-and-exceptions.md) | [#291](https://github.com/angellops/paypal-php-library/issues/291) | `REST\Config` immutable value object + full `REST\Exceptions\*` hierarchy. |
| [`phase-1/02-http-layer.md`](phase-1/02-http-layer.md) | [#292](https://github.com/angellops/paypal-php-library/issues/292) | `Http\{TransportInterface, CurlTransport, Request, Response, RequestOptions, Prefer}`. SSL verify ON by default. |
| [`phase-1/03-auth-and-tokens.md`](phase-1/03-auth-and-tokens.md) | [#293](https://github.com/angellops/paypal-php-library/issues/293) | `Auth\{OAuth2Authenticator, AccessToken, AuthAssertionBuilder}` + `TokenStore\{Interface, InMemory, Filesystem, Psr16}`. |
| [`phase-1/04-base-classes-and-facade.md`](phase-1/04-base-classes-and-facade.md) | [#294](https://github.com/angellops/paypal-php-library/issues/294) | `Responses\BaseResponse`, `Resources\BaseResource`, `REST\Client` facade with lazy resource properties. |
| [`phase-1/05-support-utilities.md`](phase-1/05-support-utilities.md) | [#295](https://github.com/angellops/paypal-php-library/issues/295) | `Support\{Logger, Json}`; integrate `Support\PartnerAttribution::VALUE` into REST request headers. |
| [`phase-1/06-phase-1-integration-test.md`](phase-1/06-phase-1-integration-test.md) | [#296](https://github.com/angellops/paypal-php-library/issues/296) | End-to-end Phase 1 sanity test: Config → Client → OAuth → mock Transport → DTO. |

## Phase 2 — REST Resource Handlers (Weeks 4-9, 14 files)

One file per resource. Each ships: resource class, typed DTO(s), unit tests with captured-response fixtures, sandbox integration test (happy path), `documentation/rest/<resource>.md` page.

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-2/01-orders.md`](phase-2/01-orders.md) | [#297](https://github.com/angellops/paypal-php-library/issues/297) | `Resources\Orders` — POST /v2/checkout/orders + show/authorize/capture/patch. |
| [`phase-2/02-payments.md`](phase-2/02-payments.md) | [#298](https://github.com/angellops/paypal-php-library/issues/298) | `Resources\Payments` — v2 authorizations/captures/refunds. |
| [`phase-2/03-webhooks.md`](phase-2/03-webhooks.md) | [#299](https://github.com/angellops/paypal-php-library/issues/299) | `Resources\Webhooks` — v1 CRUD + event types. |
| [`phase-2/04-webhook-verifier.md`](phase-2/04-webhook-verifier.md) | [#300](https://github.com/angellops/paypal-php-library/issues/300) | `Resources\WebhookVerifier` — calls verify-webhook-signature API only. |
| [`phase-2/05-subscriptions.md`](phase-2/05-subscriptions.md) | [#301](https://github.com/angellops/paypal-php-library/issues/301) | `Resources\Subscriptions` — v1/billing/subscriptions. |
| [`phase-2/06-plans.md`](phase-2/06-plans.md) | [#302](https://github.com/angellops/paypal-php-library/issues/302) | `Resources\Plans` — v1/billing/plans. |
| [`phase-2/07-catalog-products.md`](phase-2/07-catalog-products.md) | [#303](https://github.com/angellops/paypal-php-library/issues/303) | `Resources\CatalogProducts` — v1/catalogs/products. |
| [`phase-2/08-invoicing.md`](phase-2/08-invoicing.md) | [#304](https://github.com/angellops/paypal-php-library/issues/304) | `Resources\Invoicing` — v2 invoices + templates + QR code. |
| [`phase-2/09-payouts.md`](phase-2/09-payouts.md) | [#305](https://github.com/angellops/paypal-php-library/issues/305) | `Resources\Payouts` — v1 batch + items + unclaimed. |
| [`phase-2/10-disputes.md`](phase-2/10-disputes.md) | [#306](https://github.com/angellops/paypal-php-library/issues/306) | `Resources\Disputes` — v1/customer/disputes. |
| [`phase-2/11-vault.md`](phase-2/11-vault.md) | [#307](https://github.com/angellops/paypal-php-library/issues/307) | `Resources\Vault` — v3 payment-tokens + setup-tokens. |
| [`phase-2/12-partner-referrals.md`](phase-2/12-partner-referrals.md) | [#308](https://github.com/angellops/paypal-php-library/issues/308) | `Resources\PartnerReferrals` — v2/customer/partner-referrals. |
| [`phase-2/13-identity.md`](phase-2/13-identity.md) | [#309](https://github.com/angellops/paypal-php-library/issues/309) | `Resources\Identity` — v1/identity/openidconnect/userinfo, client_credentials only. |
| [`phase-2/14-reports.md`](phase-2/14-reports.md) | [#310](https://github.com/angellops/paypal-php-library/issues/310) | `Resources\Reports` — v1 transactions + balances. |

## Phase 3 — Legacy Adapter & Upgrade Path (Weeks 10-12, 13 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-3/01-legacy-foundation.md`](phase-3/01-legacy-foundation.md) | [#311](https://github.com/angellops/paypal-php-library/issues/311) | `Legacy\{RESTBackend, ResponseShaper, ErrorTranslator, UnmappableMethods, MapperInterface, FieldMap}`. |
| [`phase-3/02-ec-token-bridge.md`](phase-3/02-ec-token-bridge.md) | [#312](https://github.com/angellops/paypal-php-library/issues/312) | `Legacy\EcTokenBridge` — synthetic EC-XXX ↔ REST order_id (accepts both forms on lookup). |
| [`phase-3/03-express-checkout-mappers.md`](phase-3/03-express-checkout-mappers.md) | [#313](https://github.com/angellops/paypal-php-library/issues/313) | `SetExpressCheckout`, `DoExpressCheckoutPayment`, `GetExpressCheckoutDetails` mappers. |
| [`phase-3/04-auth-capture-mappers.md`](phase-3/04-auth-capture-mappers.md) | [#314](https://github.com/angellops/paypal-php-library/issues/314) | `DoAuthorization`, `DoCapture`, `DoReauthorization`, `DoVoid` mappers. |
| [`phase-3/05-refund-search-mappers.md`](phase-3/05-refund-search-mappers.md) | [#315](https://github.com/angellops/paypal-php-library/issues/315) | `RefundTransaction`, `GetTransactionDetails`, `TransactionSearch`, `DoReferenceTransaction` mappers. |
| [`phase-3/06-direct-mass-mappers.md`](phase-3/06-direct-mass-mappers.md) | [#316](https://github.com/angellops/paypal-php-library/issues/316) | `DoDirectPayment`, `MassPay` (→ Payouts v1) mappers. |
| [`phase-3/07-recurring-mappers.md`](phase-3/07-recurring-mappers.md) | [#317](https://github.com/angellops/paypal-php-library/issues/317) | `CreateRecurringPaymentsProfile` (Plans + Subscriptions orchestration) + 5 sibling mappers. |
| [`phase-3/08-billing-agreement-mappers.md`](phase-3/08-billing-agreement-mappers.md) | [#318](https://github.com/angellops/paypal-php-library/issues/318) | `CreateBillingAgreement` (→ Vault v3), `BillAgreementUpdate` mappers. |
| [`phase-3/09-invoicing-mappers.md`](phase-3/09-invoicing-mappers.md) | [#319](https://github.com/angellops/paypal-php-library/issues/319) | 5 invoicing mappers (Create / Send / Get / Update / Cancel). |
| [`phase-3/10-other-mappers.md`](phase-3/10-other-mappers.md) | [#320](https://github.com/angellops/paypal-php-library/issues/320) | `ManagePendingTransactionStatus`, `GetBalance` (→ Reports::balances) mappers. |
| [`phase-3/11-paypal-php-dispatch-hook.md`](phase-3/11-paypal-php-dispatch-hook.md) | [#321](https://github.com/angellops/paypal-php-library/issues/321) | Wire `private ?Legacy\RESTBackend $backend` + dispatch hook into the 30 mapped public methods on `PayPal.php`. Emit PSR-3 NOTICE on construction listing auto-fallback methods. |
| [`phase-3/12-upgrade-check-cli.md`](phase-3/12-upgrade-check-cli.md) | [#322](https://github.com/angellops/paypal-php-library/issues/322) | `bin/paypal-upgrade-check` using `nikic/php-parser` (AST-based, 4 classification buckets). |
| [`phase-3/13-legacy-exceptions.md`](phase-3/13-legacy-exceptions.md) | [#323](https://github.com/angellops/paypal-php-library/issues/323) | `Legacy\Exceptions\{UnmappableMethodException, LegacyConfigException}` + auto-fallback dispatch logic. |

## Phase 4 — Templates / Samples / Demos / Helper (Week 13, 6 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-4/01-templates-rest-wipe-rebuild.md`](phase-4/01-templates-rest-wipe-rebuild.md) | [#324](https://github.com/angellops/paypal-php-library/issues/324) | Wipe stale `templates/rest/`, regenerate ~32 blank shells aligned to new REST architecture. |
| [`phase-4/02-samples-rest-wipe-rebuild.md`](phase-4/02-samples-rest-wipe-rebuild.md) | [#325](https://github.com/angellops/paypal-php-library/issues/325) | Wipe stale `samples/rest/`, regenerate ~32 populated runnable samples. |
| [`phase-4/03-demo-rest-checkout-standard.md`](phase-4/03-demo-rest-checkout-standard.md) | [#326](https://github.com/angellops/paypal-php-library/issues/326) | `demo/rest/checkout-standard/` (5 files, JS SDK Smart Buttons via `ButtonHelper`). |
| [`phase-4/04-demo-rest-checkout-redirect.md`](phase-4/04-demo-rest-checkout-redirect.md) | [#327](https://github.com/angellops/paypal-php-library/issues/327) | `demo/rest/checkout-redirect/` (6 files mirroring `demo/classic/express-checkout-basic/`). |
| [`phase-4/05-support-button-helper.md`](phase-4/05-support-button-helper.md) | [#328](https://github.com/angellops/paypal-php-library/issues/328) | `Support\ButtonHelper::renderSmartButtons()` — emits JS SDK `<script>` tag with hardcoded `WekoodoLLC_Ecom` Partner-Attribution-Id. |
| [`phase-4/06-config-sample-update.md`](phase-4/06-config-sample-update.md) | [#329](https://github.com/angellops/paypal-php-library/issues/329) | Update `samples/config/config-sample.php` with all new config keys + inline documentation. |

## Phase 5 — Documentation (Week 14, 4 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-5/01-upgrade-from-classic-doc.md`](phase-5/01-upgrade-from-classic-doc.md) | [#330](https://github.com/angellops/paypal-php-library/issues/330) | `documentation/upgrade-from-classic.md` — 5-step upgrade walkthrough, the 30 mapped methods table, auto-fallback + unmappable lists. |
| [`phase-5/02-rest-resource-docs.md`](phase-5/02-rest-resource-docs.md) | [#331](https://github.com/angellops/paypal-php-library/issues/331) | `documentation/rest/index.md` + 14 per-resource pages. |
| [`phase-5/03-js-sdk-and-webhooks-docs.md`](phase-5/03-js-sdk-and-webhooks-docs.md) | [#332](https://github.com/angellops/paypal-php-library/issues/332) | `documentation/js-sdk-upgrade-guide.md` + `documentation/webhooks.md`. |
| [`phase-5/04-readme-changelog-migration.md`](phase-5/04-readme-changelog-migration.md) | [#333](https://github.com/angellops/paypal-php-library/issues/333) | `migration-from-v3.md` + `brand-history.md` + `README.md` + `CHANGELOG.md` v4.0.0 entry. |

## Phase 6 — Verification & RC Bake (Week 15, 2 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-6/01-manual-demo-verifications.md`](phase-6/01-manual-demo-verifications.md) | [#334](https://github.com/angellops/paypal-php-library/issues/334) | The 5 manual demo walkthroughs from PRD §5. |
| [`phase-6/02-rc-tag-and-bake.md`](phase-6/02-rc-tag-and-bake.md) | [#335](https://github.com/angellops/paypal-php-library/issues/335) | Pre-release checklist sweep, tag `v4.0.0-rc1`, 1-week bake. |

## Phase 7 — Release & Brand Cutover (Week 16, 2 files)

| Plan file | Issue | Summary |
|---|---|---|
| [`phase-7/01-ga-cutover.md`](phase-7/01-ga-cutover.md) | [#336](https://github.com/angellops/paypal-php-library/issues/336) | Coordinated GA event: composer rename + README/CHANGELOG brand updates + GitHub repo transfer + Packagist publish + tag `v4.0.0`. |
| [`phase-7/02-oob-followups.md`](phase-7/02-oob-followups.md) | [#337](https://github.com/angellops/paypal-php-library/issues/337) | Post-GA: Packagist UI abandoned flag on `angelleye/paypal-php-library`, social/brand asset updates, early-adopter issue triage. |

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
