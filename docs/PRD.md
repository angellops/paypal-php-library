# PayPal PHP SDK — REST Modernization Plan & PRD

*Target release: **v4.0.0** (the Wekoodo rebrand). Current stable: **v3.0.5**, published as `angelleye/paypal-php-library`.*

## Context

The PayPal PHP SDK at `/home/angellops/projects/paypal-sdk-php/` is a long-standing open-source library used by many merchants to integrate PayPal Classic (NVP) APIs. PayPal has effectively deprecated the Classic stack — endpoints still respond, but no new feature work is happening there, and PayPal's modern integration patterns (Smart Buttons, Pay Later, Venmo, advanced cards, marketplaces) all live on the REST stack.

**Brand history.** This library was originally released under the **Angell EYE** brand (Packagist: `angelleye/paypal-php-library`, GitHub: `angelleye/paypal-php-library`). The Angell EYE brand was sold, and the GitHub repo was migrated to the **angellops** org (with redirects in place). The library is now moving under another business — **Wekoodo** — which becomes the canonical brand going forward. v4.0 is the rebrand release: the GitHub repo migrates from `angellops` to the `Wekoodo` org, a new `wekoodo/paypal-php-library` package is published on Packagist, and the existing `angelleye/paypal-php-library` package is marked abandoned with a pointer to the new name. **The PHP namespace `angelleye\PayPal` is preserved** so existing merchants upgrade with zero `use` statement changes — the rebrand is visible in package name, GitHub org, README/docs, the JS SDK Partner-Attribution-Id default (`WekoodoLLC_Ecom`), and prominent "Formerly Angell EYE" notices throughout the project, but invisible to running code.

**Why now.** Merchants on the Classic integration are accumulating technical debt and missing PayPal product features. A previous attempt at REST support (the `vendor/paypal/rest-api-sdk-php` SDK + bridge classes like `RestClass.php`, `CheckoutOrdersClass.php`, `InvoicingClass.php`) is itself abandoned by PayPal and was already gutted in the `219` cleanup. Without a clean REST path, merchants will either fork the library, migrate to a competitor, or build raw REST integrations themselves.

**What we're building.** A modernized v4.0 of the SDK that ships full REST support alongside the existing Classic API, plus an "upgrade path" that lets existing Classic integrations transparently route through REST by flipping a single config flag. New integrations start REST-native; legacy integrations get a non-breaking ramp. The release is also the Wekoodo brand rollout.

**Branch.** Continuing on `219_ai`. The OAuth + Orders v2 prototype in `src/angelleye/PayPal/PayPalREST.php` (~410 lines) is structurally sound and gets refactored into the new architecture rather than thrown away.

---

## 1. Executive Summary

**Problem Statement.** Merchants integrated against the Angell EYE PayPal PHP library are locked into PayPal's deprecated Classic NVP stack, blocking access to modern PayPal features (Smart Buttons, Pay Later, Venmo, advanced cards, marketplaces, Subscriptions v1) and exposing them to eventual endpoint deprecation. The library's previous REST support depended on a vendor SDK PayPal has abandoned, leaving orphaned code that fatal-errors on autoload (`RestClass.php`, `CheckoutOrdersClass.php`, etc. all `use PayPal\Common\PayPalModel` from a vendor lib no longer required by `composer.json`).

**Proposed Solution.** Ship v4.0 with a clean, vendor-SDK-free REST layer covering PayPal's full REST surface (Orders, Payments, Subscriptions, Plans, Catalog Products, Invoicing, Payouts, Disputes, Webhooks, Identity, Vault, Partner Referrals, Reports). Provide a Legacy Adapter that routes Classic-style method calls through REST when merchants flip `upgrade_from_classic = true`, preserving their existing call sites and response-shape expectations. Existing Classic NVP code paths remain unchanged for merchants who don't opt in.

**Success Criteria.**
- **Zero-touch upgrade for the 32 most-used Classic methods**: a merchant's existing `$PayPal->SetExpressCheckout(...)` / `DoExpressCheckoutPayment(...)` / `RefundTransaction(...)` etc. continues to return the same response shape after flipping `upgrade_from_classic = true`, verified by running every demo in `demo/classic/` end-to-end against both backends and diffing outputs.
- **Full REST surface coverage**: 13 resource handlers shipped (Orders, Payments, Subscriptions, Plans, CatalogProducts, Invoicing, Payouts, Disputes, Webhooks, WebhookVerifier, Identity, Vault, PartnerReferrals, Reports).
- **Test coverage ≥ 80% line coverage** on the new `REST/`, `Legacy/`, and `Support/` namespaces, plus passing sandbox integration tests for at least one happy-path scenario per resource.
- **Hardcoded telemetry removed**: zero outbound HTTP calls from the SDK to non-PayPal endpoints (verified by code audit).
- **Backward compatibility**: every existing public method signature on `angelleye\PayPal\PayPal` continues to exist with the same signature and return-shape contract; existing demos in `demo/classic/` continue to work without any code change.

---

## 2. User Experience & Functionality

### User Personas

- **Persona A — Existing Classic merchant.** Has a working integration against `angelleye\PayPal\PayPal`. Calls `SetExpressCheckout` / `DoExpressCheckoutPayment` etc. with NVP-style associative arrays. Wants to migrate to REST without rewriting application code.
- **Persona B — New merchant integrator.** Starting a fresh integration in 2026+. Wants modern, idiomatic PHP — typed responses, exceptions on errors, OAuth-based auth, Smart Buttons / JS SDK on the front-end.
- **Persona C — Open-source contributor.** Submits PRs to the library. Needs clear architecture, good test scaffolding, and PSR-compliant code.

*(The library is published by Wekoodo as the PayPal partner; every merchant integration silently sends the Wekoodo `Partner-Attribution-Id` (`WekoodoLLC_Ecom`) on every API call and via the JS SDK. The value is hardcoded as a class constant in the same place the existing `APIButtonSource` is set today on `angelleye\PayPal\PayPal` — deliberately not exposed as a config key, so merchants don't see it and don't accidentally change it. There is no "platform integrator" persona to support and no per-call BN-code override surface in v4.0.)*

### User Stories & Acceptance Criteria

**Story 1 (Persona A).** As an existing Classic merchant, I want to flip a single config flag and have all my existing API calls routed through REST so my integration survives Classic deprecation without code changes.

- **AC1.1**: Adding `'upgrade_from_classic' => true` + `'ClientID' => ...` + `'ClientSecret' => ...` to my existing config array routes the 32 in-scope Classic methods through REST.
- **AC1.2**: Response shape from `SetExpressCheckout` / `DoExpressCheckoutPayment` / `GetExpressCheckoutDetails` / `DoCapture` / `DoAuthorization` / `DoVoid` / `RefundTransaction` / `GetTransactionDetails` / `TransactionSearch` / `CreateRecurringPaymentsProfile` / etc. continues to include `ACK`, `CORRELATIONID`, `TIMESTAMP`, `VERSION`, and the per-method response fields my code reads (`TOKEN`, `REDIRECTURL`, `PAYMENTINFO_0_TRANSACTIONID`, `REFUNDTRANSACTIONID`, etc.).
- **AC1.3**: When a Classic method has no clean REST equivalent (e.g., `BMCreateButton`, `DoNonReferencedCredit`, `AddressVerify`), calling it throws `UnmappableMethodException` with a structured message naming the recommended replacement, OR transparently falls back to Classic NVP if my Classic credentials are present and `'classic_methods_passthrough' => ['BMCreateButton', ...]` lists the method.
- **AC1.4**: My existing static PayPal button graphic continues to work — the upgrade does NOT require me to add the JS SDK.
- **AC1.5**: Running `vendor/bin/paypal-upgrade-check ./src` against my codebase produces a report listing every Classic method call site, classified as cleanly-upgradable, upgradable-with-caveats, or unmappable.

**Story 2 (Persona B).** As a new merchant integrator, I want a modern REST-first PHP API so I can build a clean integration from day one.

- **AC2.1**: `new \angelleye\PayPal\REST\Client($config)` returns a facade exposing lazy resource handlers (`$client->orders`, `$client->payments`, `$client->subscriptions`, etc.).
- **AC2.2**: Each resource method returns a typed DTO (e.g., `OrderResponse`) that exposes `->id`, `->status`, `->debugId()`, `->captureId()`, etc. DTOs are also `ArrayAccess`-compatible for migration ergonomics.
- **AC2.3**: PayPal API errors throw typed exceptions (`AuthenticationException`, `UnprocessableEntityException`, `RateLimitException`, etc.) all extending `PayPalApiException`, which exposes `$debugId`, `$errorName`, `$details[]`, and `$statusCode`.
- **AC2.4**: A `demo/rest/checkout-standard/` kit demonstrates the modern Smart Buttons flow end-to-end (JS SDK on front-end, Create Order + Capture Order on back-end via XHR).
- **AC2.5**: A `demo/rest/checkout-redirect/` kit demonstrates the server-only redirect flow for merchants who don't want the JS SDK.

**Story 3 (Persona C).** As an OSS contributor, I want clear architecture and test scaffolding so I can submit PRs without reverse-engineering everything.

- **AC3.1**: PSR-4 autoloading is declared for the new `angelleye\PayPal\REST\`, `angelleye\PayPal\Legacy\`, and `angelleye\PayPal\Support\` namespaces (legacy `angelleye\PayPal\PayPal` keeps PSR-0).
- **AC3.2**: PHPUnit is configured with `phpunit.xml` at repo root; running `vendor/bin/phpunit` exercises ≥ 80% of the new code.
- **AC3.3**: A `CONTRIBUTING.md` documents the resource handler pattern, the legacy mapper pattern, and how to add new endpoints.
- **AC3.4**: `RequestOptions` value object supports per-call `idempotencyKey` (`PayPal-Request-Id`) and `prefer` (`Prefer: return=representation` / `minimal`). `Config` accepts a `TransportInterface` injection (default `CurlTransport`, optional `GuzzleTransport` via `suggest`) and a `TokenStoreInterface` injection (default `InMemoryTokenStore`, optional `FilesystemTokenStore` / `Psr16TokenStore`). The `Partner-Attribution-Id` is a hardcoded class constant (`WekoodoLLC_Ecom`) — not a config key, not overridable through normal configuration, intentionally invisible to merchants.

### Non-Goals (v4.0)

- **No abstraction over PayPal's underlying object model.** REST DTOs are 1:1 with PayPal's JSON shapes; we do not invent merchant-friendly aliases like `customer_email` for `payer.email_address`.
- **No automatic 3-legged OAuth (Log in with PayPal user flow).** v4.0 covers `client_credentials` only; user-flow OAuth (Identity / Connect with PayPal) is API-callable but not orchestrated.
- **No async / promise-based API.** Sync only. Async is not on the roadmap; merchants needing concurrency can wrap calls with their own runtime.
- **No automatic webhook listener / HTTP server.** We ship `WebhookVerifier::verify($headers, $rawBody, $webhookId)`, but the merchant owns the HTTP endpoint that receives PayPal's POST.
- **No automatic JS SDK Smart Button rendering.** We ship a PHP helper that emits the `<script>` tag with the merchant's client_id pre-filled, but the merchant owns mounting the buttons and wiring `createOrder` / `onApprove` JavaScript callbacks.
- **No Adaptive Payments / Permissions API REST equivalents.** PayPal never built REST equivalents for these Classic features. They throw `UnmappableMethodException`.
- **No backward-incompat changes to the Classic NVP class.** Every public method signature on `angelleye\PayPal\PayPal` is preserved verbatim. Internal cleanups (extracting reference-data arrays to `Support\Reference`, removing the AWS tracker) are invisible to callers.
- **No PHP < 8.1 support.** `composer.json` floor moves to `^8.1`. Merchants on PHP 8.0 and earlier must upgrade PHP or stay on v3.x.
- **No major version churn for partial migrations.** v4.x is a single major bump; mid-major minor versions (4.1, 4.2) are additive only.

### User Flow — The Upgrade Experience

**Step 1.** Merchant adds REST credentials to their existing config:
```php
$PayPalConfig['ClientID']     = 'AYxxxxxxxxxxxxxx';   // from developer.paypal.com
$PayPalConfig['ClientSecret'] = 'EHxxxxxxxxxxxxxx';
```

**Step 2.** Merchant runs the upgrade-check CLI:
```bash
vendor/bin/paypal-upgrade-check ./src
```
Tool scans the merchant's source tree, lists every Classic method call site, classifies each as cleanly-upgradable / upgradable-with-caveats / unmappable, and prints a recommended `classic_methods_passthrough` list for the few that can't be cleanly upgraded.

**Step 3.** Merchant flips the switch:
```php
$PayPalConfig['upgrade_from_classic']        = true;
$PayPalConfig['classic_methods_passthrough'] = ['BMCreateButton'];  // from CLI output
```

**Step 4.** Merchant tests in sandbox. **Their actual production integration files run unchanged** — every existing call site to `SetExpressCheckout`, `DoExpressCheckoutPayment`, `RefundTransaction`, etc. continues to work with the same input shape and the same response shape. Existing demo/test pages (if they have them) and the bundled `demo/classic/` kits also continue to work — the demos function as a useful sanity check, but the goal is unchanged behavior across the merchant's whole codebase, not just demo pages. The PayPal redirect URL changes from `paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-...` to `paypal.com/checkoutnow?token=ORDER_ID` but the buyer experience is otherwise identical.

**Step 5.** Merchant promotes to production. Done.

---

## 3. Technical Specifications

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  Application code (merchant's app)                                  │
│                                                                     │
│  Existing path:   new PayPal($cfg)->SetExpressCheckout(...)         │
│  REST-native:     new REST\Client($cfg)->orders->create(...)        │
└─────────────────────────────────────────────────────────────────────┘
                          │
            ┌─────────────┴─────────────┐
            ▼                           ▼
┌───────────────────────┐   ┌────────────────────────────────┐
│ angelleye\PayPal\     │   │ angelleye\PayPal\REST\         │
│   PayPal              │   │   Client (facade)              │
│ (Classic NVP)         │   │                                │
│                       │   │ ├── orders        (Orders)     │
│ Each public method:   │   │ ├── payments      (Payments)   │
│ if ($this->backend)   │   │ ├── subscriptions             │
│   return $this->      │   │ ├── plans                      │
│     backend->         │   │ ├── catalogProducts            │
│     dispatch(         │   │ ├── invoicing                  │
│       __FUNCTION__,   │   │ ├── payouts                    │
│       $DataArray);    │   │ ├── disputes                   │
│ // else original NVP  │   │ ├── webhooks                   │
└───────────────────────┘   │ ├── webhookVerifier            │
            │               │ ├── identity                   │
            ▼               │ ├── vault                      │
┌───────────────────────┐   │ ├── partnerReferrals           │
│ angelleye\PayPal\     │   │ └── reports                    │
│   Legacy\RESTBackend  │   └────────────────────────────────┘
│                       │                  │
│ dispatch($method,     │                  ▼
│   $DataArray)         │   ┌────────────────────────────────┐
│   → Mappers\          │   │ angelleye\PayPal\REST\         │
│       SetExpress…     │   │   Http\TransportInterface      │
│       Mapper          │   │                                │
│                       │   │ Default: CurlTransport         │
│   Mapper:             │   │ Optional: GuzzleTransport      │
│     toRest(           │   │                                │
│       $DataArray)     │   │ + Auth\OAuth2Authenticator     │
│     → REST\Client     │   │ + TokenStore (in-mem/file/PSR) │
│         ->orders      │   │ + RequestOptions               │
│         ->create(...) │   │                                │
│     toClassic(        │   │ → throws PayPalApiException    │
│       $restResponse)  │   │   hierarchy on errors          │
│     → NVP-shaped arr  │   │ → returns typed DTOs on succ.  │
└───────────────────────┘   └────────────────────────────────┘
                                            │
                                            ▼
                            https://api-m.{sandbox.}paypal.com
```

### Proposed File Structure

```
src/angelleye/PayPal/
├── PayPal.php                                  KEEP — Classic NVP class
│                                               EDITS: remove AWS tracker (lines 94-96, 643, 3143-3258);
│                                               extract reference arrays (~lines 119-489) to Support\Reference;
│                                               add `private ?Legacy\RESTBackend $backend = null` and
│                                               dispatch hook in each mapped public method
│
├── PayFlow.php                                 KEEP — Classic Payflow gateway, no changes
├── Adaptive.php                                KEEP — Adaptive Payments (legacy), flagged @deprecated
├── Financing.php                               KEEP — Financing API (legacy), flagged @deprecated
│
├── PayPalREST.php                              CONVERT — thin @deprecated proxy delegating to REST\Client
│                                               so anyone who pulled 219_ai early stays working
│
├── REST/                                       NEW — PSR-4 autoloaded
│   ├── Client.php                              Facade with lazy resource properties
│   ├── Config.php                              Immutable value object: client_id, secret, sandbox,
│   │                                           base URLs, partner_attribution_id, log path,
│   │                                           token store, transport
│   ├── Auth/
│   │   ├── OAuth2Authenticator.php             client_credentials flow + single-flight refresh
│   │   ├── AccessToken.php                     readonly value object: token, expiry, app_id, scopes
│   │   └── AuthAssertionBuilder.php            PayPal-Auth-Assertion JWT builder (multi-party)
│   ├── TokenStore/
│   │   ├── TokenStoreInterface.php
│   │   ├── InMemoryTokenStore.php              Default
│   │   ├── FilesystemTokenStore.php            Recommended for production
│   │   └── Psr16TokenStore.php                 PSR-16 cache adapter (Redis/Memcached/etc.)
│   ├── Http/
│   │   ├── TransportInterface.php
│   │   ├── CurlTransport.php                   Default, zero-dep
│   │   ├── GuzzleTransport.php                 Optional (suggest only)
│   │   ├── Request.php                         Value object: method, url, headers, body
│   │   ├── Response.php                        Value object exposing debug_id easily
│   │   ├── Prefer.php                          Enum: REPRESENTATION | MINIMAL
│   │   └── RequestOptions.php                  Per-call: idempotency_key, prefer, auth_assertion,
│   │                                           partner_attribution_id override, custom headers
│   ├── Resources/                              All extend BaseResource, take Transport
│   │   ├── BaseResource.php
│   │   ├── Orders.php                          POST /v2/checkout/orders + show/auth/capture/patch
│   │   ├── Payments.php                        v2/payments authorizations/captures/refunds
│   │   ├── Subscriptions.php                   v1/billing/subscriptions
│   │   ├── Plans.php                           v1/billing/plans
│   │   ├── CatalogProducts.php                 v1/catalogs/products
│   │   ├── Invoicing.php                       v2/invoicing/invoices + templates + qr code
│   │   ├── Payouts.php                         v1/payments/payouts (batch + items + unclaimed)
│   │   ├── Disputes.php                        v1/customer/disputes
│   │   ├── Webhooks.php                        v1/notifications/webhooks (CRUD + event types)
│   │   ├── WebhookVerifier.php                 v1/notifications/verify-webhook-signature
│   │   ├── Identity.php                        v1/identity/openidconnect/userinfo
│   │   ├── Vault.php                           v3/vault/payment-tokens + setup-tokens
│   │   ├── PartnerReferrals.php                v2/customer/partner-referrals
│   │   └── Reports.php                         v1/reporting/transactions + balances
│   ├── Responses/                              Typed DTOs, ArrayAccess for migration
│   │   ├── BaseResponse.php
│   │   ├── OrderResponse.php
│   │   ├── CaptureResponse.php
│   │   ├── AuthorizationResponse.php
│   │   ├── RefundResponse.php
│   │   ├── SubscriptionResponse.php
│   │   ├── InvoiceResponse.php
│   │   ├── PayoutBatchResponse.php
│   │   ├── DisputeResponse.php
│   │   ├── WebhookEventResponse.php
│   │   ├── VaultedTokenResponse.php
│   │   ├── PartnerReferralResponse.php
│   │   └── TransactionSearchResponse.php
│   └── Exceptions/
│       ├── PayPalException.php                 Abstract base
│       ├── PayPalApiException.php              Carries debug_id, errorName, details, statusCode
│       ├── AuthenticationException.php         401
│       ├── AuthorizationException.php          403
│       ├── ResourceNotFoundException.php       404
│       ├── ResourceConflictException.php       409 (PREVIOUS_REQUEST_IN_PROGRESS)
│       ├── UnprocessableEntityException.php    422 (INSTRUMENT_DECLINED, validation)
│       ├── RateLimitException.php              429 — surfaces Retry-After
│       ├── ServerException.php                 5xx
│       ├── TransportException.php              cURL / network
│       ├── ValidationException.php             Client-side, before HTTP
│       └── ConfigurationException.php          Missing client_id, bad sandbox flag, etc.
│
├── Legacy/                                     NEW — PSR-4 autoloaded
│   ├── RESTBackend.php                         Dispatcher used by PayPal.php when
│   │                                           upgrade_from_classic = true
│   ├── EcTokenBridge.php                       Synthetic EC-XXX token ↔ REST order_id mapping
│   │                                           (uses TokenStore: session/file/redis/database)
│   ├── ResponseShaper.php                      REST JSON → NVP-shaped array helpers
│   │                                           (synthesizes ACK, CORRELATIONID, TIMESTAMP, VERSION)
│   ├── ErrorTranslator.php                     PayPalApiException → NVP ERRORS array
│   ├── UnmappableMethods.php                   Static list of methods with no REST equivalent
│   ├── Mappers/
│   │   ├── MapperInterface.php
│   │   ├── SetExpressCheckoutMapper.php
│   │   ├── DoExpressCheckoutPaymentMapper.php
│   │   ├── GetExpressCheckoutDetailsMapper.php
│   │   ├── DoCaptureMapper.php
│   │   ├── DoAuthorizationMapper.php
│   │   ├── DoReauthorizationMapper.php
│   │   ├── DoVoidMapper.php
│   │   ├── RefundTransactionMapper.php
│   │   ├── GetTransactionDetailsMapper.php
│   │   ├── TransactionSearchMapper.php
│   │   ├── DoReferenceTransactionMapper.php
│   │   ├── DoDirectPaymentMapper.php
│   │   ├── MassPayMapper.php                   → Payouts v1 (with behavioral warning)
│   │   ├── CreateRecurringPaymentsProfileMapper.php  → Plans + Subscriptions orchestration
│   │   ├── GetRecurringPaymentsProfileDetailsMapper.php
│   │   ├── ManageRecurringPaymentsProfileStatusMapper.php
│   │   ├── UpdateRecurringPaymentsProfileMapper.php
│   │   ├── BillOutstandingAmountMapper.php
│   │   ├── GetRecurringPaymentsProfileStatusMapper.php
│   │   ├── CreateBillingAgreementMapper.php    → Vault v3
│   │   ├── BillAgreementUpdateMapper.php
│   │   ├── CreateInvoiceMapper.php
│   │   ├── SendInvoiceMapper.php
│   │   ├── GetInvoiceDetailsMapper.php
│   │   ├── UpdateInvoiceMapper.php
│   │   ├── CancelInvoiceMapper.php
│   │   ├── DoCaptureMapper.php
│   │   ├── ManagePendingTransactionStatusMapper.php
│   │   ├── GetBalanceMapper.php                → Reports::balances
│   │   └── FieldMap.php                        Constant: simple 1:1 leaf mappings (~200 entries)
│   └── Exceptions/
│       ├── UnmappableMethodException.php       Thrown for BMCreateButton, AddressVerify, etc.
│       └── LegacyConfigException.php           e.g. ClientID missing when upgrade_from_classic=true
│
├── Support/                                    NEW — PSR-4 autoloaded; shared utilities
│   ├── Reference.php                           Countries/States/AVS/CVV2/Currencies
│   │                                           (extracted from PayPal.php:119-489)
│   ├── Logger.php                              PSR-3 LoggerInterface impl with debug_id-aware formatter
│   ├── Json.php                                json_encode/decode wrappers with consistent error handling
│   └── PartnerAttribution.php                  Single class constant `VALUE = 'WekoodoLLC_Ecom'` —
│                                                no setter, no override; the one source of truth
│                                                referenced by both Classic (`PayPal::$APIButtonSource`)
│                                                and REST (request headers + JS SDK helper)
│
└── ButtonHelper.php                            NEW — emits JS SDK <script> tag with merchant's
                                                client_id pre-filled, optional funding sources, currency

# Files to DELETE
src/angelleye/PayPal/RestClass.php              Orphaned, references abandoned vendor SDK
src/angelleye/PayPal/CheckoutOrdersClass.php    Orphaned
src/angelleye/PayPal/CustomerDisputesClass.php  Orphaned
src/angelleye/PayPal/EventTypesClass.php        Orphaned
src/angelleye/PayPal/InvoicingClass.php         Orphaned
src/angelleye/PayPal/PayPalSyncClass.php        Orphaned
src/angelleye/PayPal/ReferencedPayoutsClass.php Orphaned
vendor/paypal/rest-api-sdk-php/                 Abandoned vendor SDK; regenerate composer.lock

# Templates / Samples / Demos — REST counterparts (parallel sibling layout)
templates/classic/                              KEEP — 84 existing Classic templates
templates/rest/                                 NEW — REST-named blank shells (32 in v1)
samples/classic/                                KEEP — 64 existing Classic samples
samples/rest/                                   NEW — REST-named populated runnable samples (32 in v1)
demo/classic/express-checkout-basic/            KEEP — used as upgrade-path test harness
demo/rest/checkout-standard/                    NEW — modern Smart Buttons (JS SDK) demo (5 files)
demo/rest/checkout-redirect/                    NEW — server-only redirect demo (6 files, mirrors Classic)

# Test scaffolding
tests/                                          NEW
├── Unit/
│   ├── REST/
│   │   ├── Auth/OAuth2AuthenticatorTest.php
│   │   ├── Http/CurlTransportTest.php
│   │   ├── Http/RequestOptionsTest.php
│   │   ├── Resources/OrdersTest.php            (one per resource)
│   │   └── ...
│   ├── Legacy/
│   │   ├── RESTBackendTest.php
│   │   ├── EcTokenBridgeTest.php
│   │   ├── ResponseShaperTest.php
│   │   └── Mappers/                            One round-trip test per mapper
│   │       ├── SetExpressCheckoutMapperTest.php
│   │       └── ...
│   └── Support/
│       ├── ReferenceTest.php
│       └── LoggerTest.php
├── Integration/                                Gated behind PAYPAL_INTEGRATION_TESTS=1
│   ├── REST/
│   │   ├── OrdersHappyPathTest.php
│   │   └── ... (one per resource)
│   └── Legacy/
│       └── DemoUpgradeRoundtripTest.php        Runs demo/classic/ flows against both backends, diffs
└── Fixtures/
    ├── responses/                              Captured PayPal sandbox responses for Unit tests
    └── classic-requests/                       Captured Classic NVP request shapes

phpunit.xml                                     NEW — at repo root
bin/paypal-upgrade-check                        NEW — CLI scanner for merchant codebases
```

### Integration Points

- **PayPal REST API** — `https://api-m.{sandbox.}paypal.com`. OAuth 2.0 client_credentials. All endpoints listed under `Resources/` above. JSON request/response.
- **Partner-Attribution-Id** — every REST request and the JS SDK helper send `WekoodoLLC_Ecom`. The value is a single hardcoded class constant in `Support\PartnerAttribution::VALUE` — referenced by the legacy `PayPal::$APIButtonSource` assignment (replacing today's `AngellEYELLC_Ecom_PHPCatalog` literal) and by every REST request header. It is **not** a config key; merchants don't see it and can't change it through their config array. (Determined contributors can patch it in code if they fork the library, but that's not a supported integration mode.)
- **Composer** — `composer.json` updates: `require` PHP floor to `^8.1`; add `psr/log` and `psr/simple-cache`; add `guzzlehttp/guzzle` and `nikic/php-parser` (dev) as `suggest`; declare PSR-4 autoload for the new namespaces while keeping the existing PSR-0 declaration for `angelleye\\PayPal`. **Package name moves from `angelleye/paypal-php-library` to `wekoodo/paypal-php-library`** at the v4.0.0 release. The existing Packagist entry `angelleye/paypal-php-library` is marked `"abandoned": "wekoodo/paypal-php-library"` so existing merchants see Composer's "package abandoned, use X instead" prompt on their next `composer update`.
- **GitHub** — repo migrates from `github.com/angellops/paypal-php-library` to `github.com/Wekoodo/paypal-php-library` as part of the v4.0 release. GitHub's automatic redirects keep the angellops URL working for existing clones; new contributors and Packagist see the Wekoodo URL canonically. The previous `angelleye/paypal-php-library` GitHub URL also continues to redirect (chained: angelleye → angellops → Wekoodo). Update `composer.json` `homepage` and `support.source` URLs accordingly.
- **Merchant config** — single associative array passed to constructor. New keys: `ClientID`, `ClientSecret`, `upgrade_from_classic`, `classic_methods_passthrough`, `TokenStore`, `TokenStorePath`, `TokenStoreTTL`, `RESTLogPath`, `on_unmappable_method`, `on_rest_error`. All have sensible defaults. (`PartnerAttributionId` is intentionally **not** a config key — see Partner-Attribution-Id bullet above.)
- **PayPal JS SDK** — emitted via `ButtonHelper::renderSmartButtons([...])`. Loads `https://www.paypal.com/sdk/js?client-id={ClientID}&currency=USD&components=buttons` and the merchant-supplied init code, with the hardcoded Wekoodo Partner-Attribution-Id passed via the `data-partner-attribution-id` script attribute.
- **TokenStore drivers** — session (default for upgrade-mode EC-token bridge), filesystem, PSR-16 cache.

### Security & Privacy

- **AWS telemetry tracker REMOVED** — `PayPal.php:94-96` (constructor init), `PayPal.php:643` (call site in `CURLRequest`), and `PayPal.php:3143-3258` (`TPV_Parse_Request` + `TPV_Send_Request`) deleted in the Phase 0 cleanup commit. `CHANGELOG.md` documents the removal: *"Removed undocumented payment telemetry that previously sent transaction metadata to an external endpoint. The endpoint and its API key have been decommissioned."* The Wekoodo / Angell EYE side has confirmed the AWS API key has already been killed at AWS, so the cleanup is purely code-side — no rotation needed, no replacement service is being introduced.
- **SSL verification enabled by default** — `CurlTransport` sets `CURLOPT_SSL_VERIFYPEER => true` and `CURLOPT_SSL_VERIFYHOST => 2`. Disabling requires explicit `Config::disableSslVerify()` call which logs a warning. Note that the legacy `PayPal::CURLRequest` (`PayPal.php:621`) currently disables verification — fix it in this same release as a defense-in-depth improvement.
- **Client secret never logged** — `Logger` redacts `client_secret`, `Authorization` header, `access_token` from log output. Existing `MaskAPIResult` (`PayPal.php:942`) is reused / modernized for the same purpose on Classic NVP requests.
- **Token storage on disk is restricted** — `FilesystemTokenStore` writes with `0600` permissions and refuses to operate if the configured directory is world-readable.
- **`debug_id` always logged on errors** — every `PayPalApiException` carries a `debug_id` and the default `Logger` formatter prefixes error log lines with `[debug_id=...]`. Without this, PayPal Support cannot triage merchant tickets.
- **Webhook signature verification** — `WebhookVerifier::verify($headers, $rawBody, $webhookId)` calls PayPal's `/v1/notifications/verify-webhook-signature` and returns boolean. Documented in `documentation/webhooks.md` with a complete example.
- **No outbound HTTP to non-PayPal endpoints** — verified by code audit + integration test that asserts `Transport::send` URLs are `*.paypal.com`.

---

## 4. Risks & Roadmap

### Phased Rollout

**Phase 0 — Cleanup & Foundation (Week 1).**
- Delete orphaned legacy REST classes (`RestClass.php`, `CheckoutOrdersClass.php`, `CustomerDisputesClass.php`, `EventTypesClass.php`, `InvoicingClass.php`, `PayPalSyncClass.php`, `ReferencedPayoutsClass.php`).
- Delete `vendor/paypal/rest-api-sdk-php/` and regenerate `composer.lock`.
- Remove AWS tracker from `PayPal.php` (lines 94-96, 643, 3143-3258). The endpoint is already decommissioned at AWS; this is the code-side companion.
- Update the BN code (`Partner-Attribution-Id` for REST, `APIButtonSource` for Classic): create `Support\PartnerAttribution` with a single `public const VALUE = 'WekoodoLLC_Ecom';`, then replace every literal occurrence of `AngellEYELLC_Ecom_PHPCatalog` in the codebase with a reference to that constant. Specifically: `PayPal.php` line where `$this->APIButtonSource = 'AngellEYELLC_Ecom_PHPCatalog';` is set in the constructor (becomes `$this->APIButtonSource = \angelleye\PayPal\Support\PartnerAttribution::VALUE;`), and `PayPalREST.php:56,78` (the `Partner-Attribution-Id:` header lines, soon to be moved into the new `REST\Http` layer). Keep this hardcoded — do NOT add a config key for it. The whole point is that merchants don't see it and don't change it.
- Bump `composer.json` PHP floor to `^8.1`. Add PSR-4 autoload entries for new namespaces. Add `psr/log`, `psr/simple-cache` to `require`. Add `guzzlehttp/guzzle` to `suggest`.
- Extract `PayPal.php:119-489` (Countries/States/AVS/CVV2/Currencies arrays) to `Support\Reference`. Update PayPal.php to load via the new class without changing public method behavior.
- Set up `phpunit.xml`, `tests/` directory structure, fixtures directory.
- Convert existing `PayPalREST.php` to a thin `@deprecated` proxy over (yet-to-be-built) `REST\Client`.

**Phase 0b — Brand transition (Week 1, parallel with cleanup).**
- Update repo metadata: `composer.json` `homepage` and `support.source` to `https://github.com/Wekoodo/paypal-php-library`. Author block can list both Angell EYE (historical) and Wekoodo (current). The package `name` field changes to `wekoodo/paypal-php-library` at the moment of v4.0.0 publish, not before.
- Update `README.md` header with prominent "**Formerly published as Angell EYE — now Wekoodo.**" notice. Include a "Brand history" section explaining: Angell EYE → angellops (GitHub-only rename) → Wekoodo (v4.0+). Note that the PHP namespace `angelleye\PayPal` is preserved for backward compatibility — no `use` statement changes needed when upgrading from v3.x.
- Replace any in-code references to "Angell EYE" with "Wekoodo" in user-facing strings (log prefixes, error messages, default `LogPath` directory name if present), keeping the literal namespace `angelleye` intact in PHP code.
- Update `CHANGELOG.md` with a v4.0.0 entry that opens with the brand change.
- Coordinate the actual GitHub org migration (`angellops` → `Wekoodo`) with the maintainer; this is a one-click GitHub admin action but should be timed with the v4.0.0 release tag so docs and the new Packagist entry land at the same moment.

**Phase 1 — Core REST Plumbing (Weeks 2-3).**
- Build `REST\Config`, `REST\Http\{Request,Response,RequestOptions,Prefer,TransportInterface,CurlTransport}`, `REST\Auth\{OAuth2Authenticator,AccessToken,AuthAssertionBuilder}`, `REST\TokenStore\{TokenStoreInterface,InMemoryTokenStore,FilesystemTokenStore,Psr16TokenStore}`, `REST\Exceptions\*`, `REST\Responses\BaseResponse`, `REST\Resources\BaseResource`, `REST\Client` (facade, lazy resources).
- Unit tests for all of the above (mocked HTTP).

**Phase 2 — REST Resource Handlers (Weeks 4-9).**
- Build all 13 resource handlers in this order (each ~3-5 days including tests):
  1. `Orders` (Week 4)
  2. `Payments` (Week 4)
  3. `Webhooks` + `WebhookVerifier` (Week 5)
  4. `Subscriptions` + `Plans` + `CatalogProducts` (Weeks 5-6)
  5. `Invoicing` (Week 6)
  6. `Payouts` (Week 7)
  7. `Disputes` (Week 7)
  8. `Vault` (Week 8)
  9. `PartnerReferrals` (Week 8)
  10. `Identity` (Week 9)
  11. `Reports` (Week 9)
- Each handler ships with: typed `Responses\*Response` DTO(s), unit tests against captured response fixtures, sandbox integration test for the happy path, README section in `documentation/rest/{resource}.md`.

**Phase 3 — Legacy Adapter & Upgrade Path (Weeks 10-12).**
- Build `Legacy\RESTBackend`, `Legacy\EcTokenBridge`, `Legacy\ResponseShaper`, `Legacy\ErrorTranslator`, `Legacy\UnmappableMethods`, `Legacy\Mappers\FieldMap`, `Legacy\Exceptions\*`.
- Build the 32 mappers listed under "Proposed File Structure" above.
- Wire dispatch hook into `PayPal.php` — add `private ?Legacy\RESTBackend $backend` property; in `__construct`, if `$DataArray['upgrade_from_classic']` is truthy and ClientID/Secret present, instantiate the backend; in each public method that has a corresponding mapper, prepend `if ($this->backend && $this->backend->canDispatch(__FUNCTION__)) return $this->backend->dispatch(__FUNCTION__, $DataArray);`.
- Round-trip unit tests for each mapper: input Classic NVP $DataArray → mapper → mocked REST request → mocked REST response → mapper → assert NVP-shaped output matches expected.
- Build `bin/paypal-upgrade-check` CLI using `nikic/php-parser` (added as dev dep).

**Phase 4 — Templates / Samples / Demos / Helper (Week 13).**
- Generate 32 REST templates under `templates/rest/` (REST-named, blank shells matching Classic templates style).
- Generate 32 REST samples under `samples/rest/` (REST-named, populated, runnable).
- Build `demo/rest/checkout-standard/` (5 files: `index.php` with JS SDK Smart Buttons, `create-order.php` XHR endpoint, `capture-order.php` XHR endpoint, `order-complete.php`, shared `assets/`).
- Build `demo/rest/checkout-redirect/` (6 files mirroring `demo/classic/express-checkout-basic/` file shape but using REST).
- Build `Support\ButtonHelper` for emitting JS SDK script tag.
- Update `samples/config/config-sample.php` with all new config keys and inline documentation.

**Phase 5 — Documentation (Week 14).**
- `documentation/upgrade-from-classic.md` — full upgrade walkthrough, the 32 mapped methods, the unmappable list, tradeoffs.
- `documentation/rest/index.md` — overview of the new REST architecture.
- `documentation/rest/{orders,payments,subscriptions,plans,catalog-products,invoicing,payouts,disputes,webhooks,identity,vault,partner-referrals,reports}.md` — one per resource.
- `documentation/js-sdk-upgrade-guide.md` — optional UX modernization with JS SDK Smart Buttons.
- `documentation/webhooks.md` — receiving and verifying webhooks.
- `documentation/migration-from-v3.md` — what changed structurally between v3.x and v4.x, how to upgrade (composer package rename, namespace unchanged, telemetry removed, full REST surface added).
- `documentation/brand-history.md` — the Angell EYE → angellops → Wekoodo journey, why the PHP namespace is preserved (`angelleye\PayPal` stays intact), where to find the package on Packagist now (`wekoodo/paypal-php-library` canonical, `angelleye/paypal-php-library` marked abandoned), GitHub redirects, and current contact channels under the Wekoodo brand. Linked prominently from `README.md` and `CHANGELOG.md`.
- Update `README.md` (with "**Formerly Angell EYE — now Wekoodo**" notice and brand-history link near the top), `CHANGELOG.md` (v4.0.0 entry leads with the brand change, then the REST modernization, then the telemetry removal), and any inline doc-block author/copyright lines in `src/` that mention the old brand.

**Phase 6 — End-to-End Verification & Release (Weeks 15-16).**
- Run `demo/classic/express-checkout-basic/` end-to-end against both backends (`upgrade_from_classic = false` and `true`), diff outputs, verify behavioral parity.
- Run `demo/rest/checkout-standard/` and `demo/rest/checkout-redirect/` end-to-end in sandbox.
- Run `bin/paypal-upgrade-check` against three real merchant codebases (e.g., one PayPal + WooCommerce extension, one custom checkout flow, one subscription billing app) and verify the report classifies methods correctly.
- Run full PHPUnit suite — assert ≥ 80% line coverage on `REST/`, `Legacy/`, `Support/` namespaces.
- Tag `v4.0.0-rc1`, publish to Packagist as a release candidate.
- After a 2-week RC bake period with community feedback, tag `v4.0.0`.

### Technical Risks

- **EC-token bridge fragility.** The synthetic `EC-XXX` token mapping to REST order_ids depends on a `TokenStore` and on intercepting/rewriting the merchant's `returnurl` so PayPal sends back the synthetic token rather than the raw order_id. If a merchant's framework strips query params, modifies `returnurl` post-call, or runs PayPal redirects in a context where session storage is unavailable (CLI, queued worker), the bridge breaks. **Mitigation:** the bridge accepts BOTH forms on lookup (synthetic token OR raw REST order_id), so even if the rewrite fails, `GetExpressCheckoutDetails` still resolves. Document this fallback explicitly. Add an `EcTokenBridge` integration test that simulates each session storage backend.
- **Mapper drift.** PayPal's REST and Classic responses are similar but not identical. Subtle field-shape mismatches (currency formatting, address line collapsing, line-item rounding) will surface in production for some merchants. **Mitigation:** comprehensive round-trip tests per mapper with captured real-world fixtures; explicit `behavioral_differences` notes in `documentation/upgrade-from-classic.md`; the upgrade-check CLI flags caveats inline.
- **Sandbox != live behavior.** PayPal sandbox occasionally diverges from production (test card behavior, Pay Later eligibility, Disputes lifecycle timings). Integration tests passing in sandbox don't guarantee production behavior. **Mitigation:** RC period (2 weeks) with select beta merchants running on production. Documented in `documentation/sandbox-vs-live.md`.
- **Token store concurrency.** `FilesystemTokenStore` with file-locking has worked for years in similar libraries, but high-concurrency scenarios (50+ concurrent FPM workers all hitting an expired token) can trigger a thundering herd. **Mitigation:** jittered single-flight refresh inside `OAuth2Authenticator` (refresh delay = `random_int(0, 500)` ms), with file lock as the serialization point.
- **PHP 8.1 floor excludes some merchants.** Merchants on shared hosting may still be on 8.0 or even 7.4. **Mitigation:** publish a clear deprecation notice in v3.x README and `CHANGELOG.md`; keep v3.x in `support` mode for 12 months post-v4.0 release. v3.x receives security fixes only.
- **JS SDK helper false-promises.** Merchants who expect `ButtonHelper::renderSmartButtons` to "just work" without writing any JavaScript will be disappointed — they still need to wire `createOrder` / `onApprove` callbacks. **Mitigation:** `ButtonHelper` documentation explicitly states what it does and doesn't do; `demo/rest/checkout-standard/` provides a copy-pasteable working example.
- **Adaptive Payments / Permissions / Hosted Buttons orphans.** Some Classic methods (`BMCreateButton`, `Adaptive::Pay`, `Permissions::RequestPermissions`, `DoNonReferencedCredit`, `AddressVerify`) have no REST equivalent. Merchants using them will hit `UnmappableMethodException`. **Mitigation:** the `paypal-upgrade-check` CLI flags these in advance; documentation lists every unmappable method with the recommended PayPal-side alternative; merchants can opt to keep using Classic for those specific methods via `classic_methods_passthrough`.

### Roadmap Beyond v4.0

- **v4.1.** PSR-7 `Request`/`Response` adapter for `Transport`. Async transport via Guzzle promises. Symfony Mailer-style middleware pipeline for retry / circuit breaker / observability.
- **v4.2.** 3-legged OAuth orchestration (Log in with PayPal user flow). Webhook receiver helper that owns the HTTP endpoint (with queue support).
- **v5.0.** Full PSR-4 migration of legacy classes (move `src/angelleye/PayPal/PayPal.php` to `src/PayPal.php` etc.). Drop the dispatch hook and remove the Legacy adapter entirely (Classic NVP endpoints are presumed dead at this point). This is also the natural point to evaluate whether to migrate the PHP namespace from `angelleye\PayPal` to `Wekoodo\PayPal` (with class_alias backward compat).

---

## 5. Verification Strategy

End-to-end verification runs at every Phase boundary. The full suite at the end of Phase 6 is the release gate.

### Unit Tests

```bash
vendor/bin/phpunit --testsuite Unit
```

- **Coverage gate.** ≥ 80% line coverage on `src/angelleye/PayPal/REST/`, `src/angelleye/PayPal/Legacy/`, `src/angelleye/PayPal/Support/`. Enforced via `phpunit.xml` `<coverage>` config + CI failure on regression.
- **Per-resource happy and sad paths.** Each `Resources\*` class has tests that mock `Transport::send` to return captured PayPal sandbox responses and assert the DTO is built correctly. Sad-path tests assert the right exception type is thrown for each HTTP status code.
- **Per-mapper round trip.** Each `Legacy\Mappers\*Mapper` has a test that takes a representative Classic NVP `$DataArray`, runs it through `toRestRequest`, asserts the REST request shape is correct; then takes a captured REST response, runs it through `toClassicResponse`, asserts the NVP-shaped output matches what the existing `PayPal.php` Classic method would have returned.
- **Token store concurrency.** `FilesystemTokenStore` test forks 10 PHP processes that all call `getAccessToken()` simultaneously; assert exactly ONE OAuth request fired (verified via shared mock counter file).

### Integration Tests (Sandbox)

```bash
PAYPAL_INTEGRATION_TESTS=1 \
PAYPAL_SANDBOX_CLIENT_ID=AY... \
PAYPAL_SANDBOX_CLIENT_SECRET=EH... \
PAYPAL_SANDBOX_API_USERNAME=... \
PAYPAL_SANDBOX_API_PASSWORD=... \
PAYPAL_SANDBOX_API_SIGNATURE=... \
vendor/bin/phpunit --testsuite Integration
```

- One sandbox happy-path test per resource (e.g., `OrdersHappyPathTest::testCreateAndCaptureOrder`).
- `DemoUpgradeRoundtripTest` runs the `demo/classic/express-checkout-basic/` flow end-to-end twice (once with `upgrade_from_classic = false`, once with `true`), captures the JSON-encoded response from each step, and asserts the response shapes match (excluding fields like `TIMESTAMP`, `CORRELATIONID`, `TRANSACTIONID` that are inherently per-call). Failure here is the canary for mapper drift.

### Manual Demo Verification

For each Phase 6 release-gate run:

1. **Classic-only demo.** With `upgrade_from_classic = false` and Classic credentials only, walk `http://localhost/demo/classic/express-checkout-basic/index.php` in a browser. Click "Express Checkout", complete sandbox checkout as buyer, return to demo, complete order. Verify order-complete page shows transaction ID and the response array on screen contains expected NVP fields.
2. **Upgrade-mode demo.** Same demo, same files, same browser steps — but config now has `upgrade_from_classic = true` plus REST `ClientID`/`ClientSecret`. Verify identical buyer experience and identical merchant-side response shape (TOKEN, REDIRECTURL, PAYMENTINFO_0_TRANSACTIONID, ACK="Success", etc.). The PayPal-hosted approval URL changes from `paypal.com/cgi-bin/webscr?cmd=_express-checkout` to `paypal.com/checkoutnow` — that is expected and is the visible signal that REST is active.
3. **REST-native Smart Buttons demo.** Walk `http://localhost/demo/rest/checkout-standard/index.php`. Verify JS SDK loads, Smart Buttons render, click "PayPal" button, complete in-modal checkout, return to merchant page, see order-complete with capture details.
4. **REST-native redirect demo.** Walk `http://localhost/demo/rest/checkout-redirect/index.php`. Same as #1 but file paths are `CreateOrder.php`, `CaptureOrder.php`, etc.
5. **Upgrade-check CLI.** Run `vendor/bin/paypal-upgrade-check tests/Fixtures/sample-merchant-codebases/` — verify output classifies the seeded method calls correctly across cleanly-upgradable / caveats / unmappable buckets and prints the recommended `classic_methods_passthrough` config block.

### Pre-Release Checklist

- [ ] All unit tests pass (`vendor/bin/phpunit --testsuite Unit`).
- [ ] All sandbox integration tests pass with sandbox credentials in env.
- [ ] Coverage report shows ≥ 80% on new namespaces.
- [ ] Five manual demo verifications above all pass.
- [ ] An end-to-end smoke test of at least one merchant production codebase (or a representative fixture codebase) confirms that flipping `upgrade_from_classic = true` produces identical response shapes for the merchant's actual integration files — not just the bundled demos.
- [ ] `composer install --no-dev` on a clean checkout produces a vendor tree with NO `paypal/rest-api-sdk-php` directory.
- [ ] Code search for `gtctgyk7fh.execute-api.us-east-2.amazonaws.com` returns zero hits.
- [ ] Code search for `srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa` returns zero hits.
- [ ] Code search for `TPV_Parse_Request` and `TPV_Send_Request` returns zero hits.
- [ ] Code search for `AngellEYELLC_Ecom_PHPCatalog` returns zero hits — the BN code default is `WekoodoLLC_Ecom`.
- [ ] `CHANGELOG.md` documents all breaking changes, the telemetry removal, and the brand transition.
- [ ] `documentation/upgrade-from-classic.md` walks through the full 5-step upgrade flow.
- [ ] `documentation/brand-history.md` exists and is linked from `README.md`.
- [ ] `README.md` displays the "Formerly Angell EYE — now Wekoodo" notice prominently.
- [ ] GitHub repo lives at `github.com/Wekoodo/paypal-php-library`; the angellops URL redirects correctly.
- [ ] Packagist `wekoodo/paypal-php-library` package is published; `angelleye/paypal-php-library` is marked `abandoned` pointing to the new name.
- [ ] At least 2 beta merchants have run upgrade-mode in production for 2 weeks without regressions on their actual production code paths (not just demos).

---

## Critical Files

**Files that will be modified:**
- `composer.json` — change `name` to `wekoodo/paypal-php-library`; PHP floor `^8.1`; update `homepage` and `support.source` to the new Wekoodo GitHub URL; preserve / extend the `authors` block to credit both Angell EYE (historical) and Wekoodo (current); add PSR-4 autoload for new namespaces; add `psr/log` + `psr/simple-cache`; suggest `guzzlehttp/guzzle`; suggest `nikic/php-parser` (dev); add `bin` entry for `paypal-upgrade-check`. (The OLD `composer.json` on the `release` branch under the old package name will get its own update setting `"abandoned": "wekoodo/paypal-php-library"` AFTER the v4.0.0 publish succeeds — see Out-of-Band item 3.)
- `composer.lock` — regenerate after vendor cleanup
- `autoload.php` — verify the custom SPL fallback handles PSR-4 paths for the new namespaces
- `src/angelleye/PayPal/PayPal.php` — DELETE AWS tracker (lines 94-96, 643, 3143-3258); CHANGE the `$this->APIButtonSource = 'AngellEYELLC_Ecom_PHPCatalog'` assignment (around line 87) to reference `Support\PartnerAttribution::VALUE` (which holds `'WekoodoLLC_Ecom'`); EXTRACT reference arrays (lines 119-489) to `Support\Reference`; ADD `private ?Legacy\RESTBackend $backend` property + dispatch hook in 32 public methods. PHP namespace stays `angelleye\PayPal` for backward compatibility — only doc-block author/copyright lines update to the Wekoodo brand
- `samples/config/config-sample.php` — add all new config keys with inline documentation
- `README.md` — prominent "Formerly Angell EYE — now Wekoodo" notice + brand-history link near the top; REST quickstart; upgrade walkthrough teaser
- `CHANGELOG.md` — v4.0.0 entry leads with brand change, then REST modernization, then telemetry removal

**Files that will be deleted:**
- `src/angelleye/PayPal/RestClass.php`
- `src/angelleye/PayPal/CheckoutOrdersClass.php`
- `src/angelleye/PayPal/CustomerDisputesClass.php`
- `src/angelleye/PayPal/EventTypesClass.php`
- `src/angelleye/PayPal/InvoicingClass.php`
- `src/angelleye/PayPal/PayPalSyncClass.php`
- `src/angelleye/PayPal/ReferencedPayoutsClass.php`
- `vendor/paypal/rest-api-sdk-php/` (whole directory)

**Files that will be converted (kept as a deprecated shim):**
- `src/angelleye/PayPal/PayPalREST.php` — thin `@deprecated` proxy delegating to `REST\Client` so users who pulled `219_ai` early stay working through one minor version

**New files to create:** see "Proposed File Structure" in §3 above (~120 new files, including resource handlers, response DTOs, exception classes, mappers, helpers, supporting utilities, templates, samples, demo kits, tests, fixtures, documentation).

---

## Out-of-Band Items the Maintainer Must Action

These are not code changes but are blockers for the release:

1. **AWS endpoint and key are already decommissioned** (per maintainer confirmation). No rotation work needed. The code-side cleanup (Phase 0) is the only remaining task. If telemetry is desired in the future, design it as an opt-in `TelemetryInterface` in v4.1+ with documented data fields and clear consent UX.
2. **Migrate the GitHub repository** from `github.com/angellops/paypal-php-library` to `github.com/Wekoodo/paypal-php-library`. GitHub's transfer-repository flow handles this in one click and sets up automatic redirects from the old URL. Time the transfer with the v4.0.0 release tag so docs and the new Packagist entry land at the same moment.
3. **Publish the renamed Packagist package.** Create `wekoodo/paypal-php-library` on Packagist pointing at the new GitHub repo URL. Set up Packagist's GitHub webhook so future tags auto-publish. After the v4.0.0 publish succeeds, edit the existing `angelleye/paypal-php-library` package on Packagist and set `"abandoned": "wekoodo/paypal-php-library"` in `composer.json` of the `release` branch (or via the Packagist UI). This makes Composer print the standard "package abandoned, use X instead" notice on every existing merchant's next `composer update`.
4. **Recruit 2 beta merchants** for the 2-week RC bake period. Ideal mix: one running Express Checkout for e-commerce (will exercise `SetExpressCheckout` / `DoExpressCheckoutPayment` / `RefundTransaction` mappers heavily), one running Recurring Payments (will exercise the Subscriptions mappers including the multi-call Plans + Subscription orchestration). Verify both merchants test on their actual production integration files, not just bundled demos.
5. **Publish a v3.x deprecation notice** on the existing Packagist page and the GitHub README pointing to the v4.0 RC. Set v3.x branch to security-only fixes for 12 months. Make clear that v3.x merchants do NOT need to change `use` statements when upgrading — the namespace `angelleye\PayPal` is preserved.
6. **Update social and brand assets** — Twitter/X bio, project website (if any), and any related angellops org repos to redirect users to Wekoodo. Coordinate with marketing or do solo, but ensure the brand transition is not just a code-level event.
