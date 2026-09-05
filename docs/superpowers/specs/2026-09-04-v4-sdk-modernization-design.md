# v4 SDK modernization — replacement specification

Date: 2026-09-04

Status: Design sections approved; written specification self-reviewed; awaiting final user review.

Scope: Product behavior, architecture, compatibility, and release acceptance. Implementation planning is explicitly outside this document and this session.

This specification supersedes [the old PRD](../../archive/v4-pre-replacement/PRD.md) and **every file under [the archived v4 plans](../../archive/v4-pre-replacement/v4-plans/README.md)**. Those files remain historical records, not implementation instructions. Their schedules, mapper counts, deletion lists, and linked issues do not carry approval into the replacement design. External issues and milestones have not been changed. The [brainstorming handoff](../../handoffs/2026-09-04-v4-brainstorming.md) records the preceding review and approvals.

## 1. Outcome and approved boundaries

v4 modernizes this PHP library for PayPal merchant REST integrations while keeping existing integrations serviceable. It promises seamless migration only for flows whose inputs, outputs, approval sequence, resource ownership, and payment semantics have been verified. Other flows retain their original backend where available, with an explicit migration path.

The approved architecture is an SDK-owned REST core with separate adapters for Classic NVP, existing project-owned REST interfaces, and selected lower-priority Adaptive interfaces. Wrapping PayPal's current Server SDK and generating the entire library from schemas were considered and rejected. Official schemas inform operation coverage and contract tests; they do not dictate the PHP architecture.

The approved release boundaries are:

- Comprehensive merchant REST lifecycle coverage within the explicit inventory below, plus bounded platform onboarding/status and delegated calls.
- Preserve project-owned facade contracts. Direct use of the abandoned vendor SDK is an explicit migration boundary, not a promise to emulate that SDK.
- Select a backend before a mutation. Preserve that choice through retries and reconciliation. Migration flows spanning requests require durable storage.
- PHP 8.3 minimum. Preserve namespace `angelleye\PayPal`, rebrand the canonical package to `wekoodo/paypal-php-library`, and retain GPL licensing.
- Fixed partner attribution `WekoodoLLC_Ecom`, one source of truth, with no merchant override.
- Remove outbound telemetry; secure retained transports and diagnostics; retain required Payflow helper behavior.
- Ship an installable upgrade checker with its parser as a runtime dependency. Maintain v3 security fixes for 12 months after v4 GA.

Native integrations use REST directly. Existing Classic integrations remain on Classic unless migration is explicitly enabled. Adding REST credentials alone does not switch their backend. Existing REST facades keep their established API family unless a version migration is verified or explicitly configured.

Non-goals are a marketplace settlement engine, automatic seller fund distribution, a hosted webhook service, a frontend framework, asynchronous PHP APIs, full vendor-SDK emulation, automatic migration of historical agreements/profiles, and replacing every Adaptive service with REST. Braintree, mobile SDKs, point of sale, agentic commerce, and unrelated PayPal products are outside this merchant SDK inventory.

## 2. Component boundaries

| Component | Responsibility and public contract | Dependencies |
| --- | --- | --- |
| REST client and resources | Typed synchronous methods for the listed HTTP operations; lossless request/response data; consistent request options | Immutable configuration, authentication, transport |
| Authentication | Merchant credentials, explicitly scoped delegated/user tokens, browser-token generation, token expiry and refresh | Transport, token cache, supplied credential/token providers |
| Transport | HTTP execution, TLS, response decoding, bounded operation-aware retries, sanitized diagnostics | HTTP implementation and logger; no mapper knowledge |
| Classic adapter | Original method signatures, grouped/scalar argument normalization, compatibility classification, field/result mapping | REST resources, legacy NVP backend, durable migration state |
| Existing REST adapters | Preserve each project-owned wrapper/model contract where specified; remove vendor dependency internally | REST resources and, where required, retained original REST endpoint contracts |
| Adaptive adapter | Explicitly selected service-specific mappings; otherwise preserve eligible original service paths | REST resources or original XML service transport |
| Migration state and routing | Resource origin, operation identity, concurrency and recovery records | Merchant-configured durable storage |
| Notification helpers | Webhook verification, callback support, documented event handling contracts | REST core; merchant owns HTTP endpoints and fulfillment |
| Upgrade checker | Static call-site and dependency analysis with reasons and unknowns | Shipped compatibility definitions and PHP parser; no live credentials required |

There is one REST authentication/transport/resource implementation. Adapters call it rather than constructing independent REST stacks. NVP, Adaptive XML, and Payflow retain protocol-specific behavior; shared security utilities must not change their wire formats or response envelopes.

Native configuration is immutable; changing tenant, environment, credentials, or defaults creates a new client configuration. Existing facade setters may update facade-local defaults for subsequent calls without mutating a shared native client or an in-flight operation. Request IDs belong to individual operations, not to a globally reused payment session.

## 3. Merchant REST operation inventory

This is the required v4 operation boundary, not a claim that these operations have already been implemented or sandbox-tested. Sources were reviewed on 2026-09-04. The baseline is PayPal's [OpenAPI snapshot at `90e8041ffe02d80c452d2b476bedd59a8d219bdc`](https://github.com/paypal/paypal-rest-api-specifications/tree/90e8041ffe02d80c452d2b476bedd59a8d219bdc/openapi), supplemented by current API references where the snapshot lags.

Each released operation must have a coverage entry recording method/path, API version, request and response types, auth context, documented permissions/capabilities, pagination/content type, idempotency support and retention window where documented, source revision/date, and evidence status. Evidence states are **source-reviewed**, **contract-tested**, **sandbox-verified**, or **blocked with reason**. Account-specific entitlement tests may be blocked without implying that a provider operation is absent. A blocked test never becomes a pass. API availability and merchant authorization remain PayPal decisions.

In the tables, an item suffix attaches to its stated collection; named actions use POST unless another verb is shown. Tables enumerate scope without prescribing PHP method names or a file layout.

| Family/version and base | Required operations | Eligibility and semantic boundary |
| --- | --- | --- |
| Orders v2: `/v2/checkout/orders` | POST create; GET `/{id}`; PATCH `/{id}`; POST `/{id}/confirm-payment-source`, `/authorize`, `/capture`, `/track`; PATCH `/{id}/trackers/{tracker_id}` | Payment-source eligibility, approval and order-state restrictions apply. Sale and authorization are distinct flows. |
| Payments v2: `/v2/payments` | GET `/authorizations/{id}`; POST its `/capture`, `/reauthorize`, `/void`; GET `/captures/{id}`; POST its `/refund`; GET `/refunds/{id}`; POST `/find-eligible-methods` | Preserve partial/final capture and refund semantics. No invented authorization-create endpoint. Eligibility results do not guarantee a subsequent payment. |
| Vault v3: `/v3/vault` | POST/GET-list `/payment-tokens`; GET/DELETE `/payment-tokens/{id}`; POST `/setup-tokens`; GET `/setup-tokens/{id}` | Saved-payment permission and buyer consent are required as applicable. No payment-token PUT/update operation is promised by this baseline. |
| Products v1: `/v1/catalogs/products` | POST/GET-list collection; GET/PATCH `/{id}` | Catalog records support subscription products; they do not represent buyer approval. |
| Plans v1: `/v1/billing/plans` | POST/GET-list collection; GET/PATCH `/{id}`; POST its `/activate`, `/deactivate`, `/update-pricing-schemes` | Preserve schedule, trial, pricing, tax and setup-fee semantics. |
| Subscriptions v1: `/v1/billing/subscriptions` | POST/GET-list collection; GET/PATCH `/{id}`; POST its `/revise`, `/suspend`, `/cancel`, `/activate`, `/capture`; GET its `/transactions` | Consent, lifecycle state and allowed outstanding-balance collection govern operations. Listing is in the live reference but absent from the pinned schema. |
| Payouts v1: `/v1/payments` | POST `/payouts`; GET `/payouts/{id}` and `/payouts-item/{id}`; POST `/payouts-item/{id}/cancel` | Requires Payouts eligibility. Batch acceptance and recipient settlement are separate outcomes; cancel applies to eligible unclaimed items. |
| Reporting v1: `/v1/reporting` | GET `/transactions`, `/balances`, `/get-balance-net-summary`, `/get-daily-summary` | Reporting permission and query windows apply. The two summary operations are live-reference additions. Reporting is not an immediate operational lookup substitute. |
| Identity v1 and OAuth | GET `/v1/identity/openidconnect/userinfo`; token requests through `/v1/oauth2/token` for supported client, authorization-code and refresh grants; consent URL construction | User information requires the corresponding consented user token/scopes. Client credentials cannot impersonate a user. Merchant owns login sessions, state validation and consent navigation. |

Sources: [Orders](https://developer.paypal.com/api/orders/v2/), [Payments](https://developer.paypal.com/api/payments/v2/), [Vault schema](https://github.com/paypal/paypal-rest-api-specifications/blob/90e8041ffe02d80c452d2b476bedd59a8d219bdc/openapi/vault_payment_tokens_v3.json), [Products](https://developer.paypal.com/api/catalog-products/v1/), [Subscriptions](https://developer.paypal.com/api/subscriptions/v1/), [Payouts](https://developer.paypal.com/api/payments.payouts-batch/v1/), [Reporting](https://developer.paypal.com/api/transaction-search/v1/), [Identity](https://developer.paypal.com/api/identity/v1/), [Authentication](https://developer.paypal.com/api/rest/authentication/).

### 3.1 REST Invoicing is a first-class family

The required v2 inventory below is independent of older Invoice service methods in `Adaptive.php`. It includes existing project REST invoicing contracts and current merchant features. Base: `/v2/invoicing`.

| Area | Required operations |
| --- | --- |
| Invoices | POST/GET-list `/invoices`; GET/PUT/DELETE `/invoices/{id}`; POST its `/send`, `/remind`, `/cancel`; POST `/search-invoices` |
| External records | POST `/invoices/{id}/payments` and `/refunds`; DELETE `/invoices/{id}/payments/{transaction_id}` and `/refunds/{transaction_id}` |
| Utilities | POST `/invoices/{id}/generate-qr-code`; POST `/generate-next-invoice-number` |
| Templates | POST/GET-list `/templates`; GET/PUT/DELETE `/templates/{id}` |
| Conditional rules | POST/GET-list `/invoices/{id}/conditional-rules`; GET/PUT/DELETE `/invoices/{id}/conditional-rules/{rule_id}` |
| Recurring invoice series | POST `/recurring-invoices`; GET/PUT/DELETE `/recurring-invoices/{id}`; POST its `/activate`, `/cancel`; POST `/search-recurring-invoices` |
| Automatic reminders | POST `/setup-reminders`; GET `/reminders` and `/reminders/{id}`; PUT `/reminders/{id}`; POST its `/suspend`, `/resume`; POST `/invoices/{id}/cancel-reminders` |

Recording an external payment/refund is bookkeeping, not an SDK instruction to move money. Recurring invoices are distinct from Subscriptions charging. Conditional rules and automatic reminders must retain provider semantics and account restrictions. [Current Invoicing reference](https://developer.paypal.com/api/invoicing/v2/)

The pinned schema also exposes GET `/accounting-sync/merchant/connections` and GET `/accounting-sync/invoices/{id}/connections`. These are **source-reviewed, conditional scope**: not baseline GA coverage because they are absent from the current public overview and access/contract availability has not been established. Publish that exclusion explicitly; do not invent accounting-provider connection management. This exception does not reduce ordinary invoicing lifecycle coverage.

### 3.2 Disputes and notifications

Disputes v1 uses `/v1/customer/disputes`: GET list and detail, PATCH detail, and the item actions `provide-evidence`, `appeal`, `accept-claim`, `escalate`, `send-message`, `make-offer`, `accept-offer`, `deny-offer`, `acknowledge-return-item`, and `provide-supporting-info`. These require the correct party role, permission and dispute state; buyer-only actions cannot be advertised as seller capabilities. Evidence uploads support the documented multipart contract. `adjudicate` and `require-evidence` are exposed only as explicitly sandbox-only test utilities and reject production use. [Disputes reference](https://developer.paypal.com/api/customer-disputes/v1/)

Webhooks v1 uses `/v1/notifications`:

- `/webhooks`: POST/GET-list; GET/PATCH/DELETE `/{id}`; GET `/{id}/event-types`.
- `/webhooks-lookup`: POST/GET-list; GET/DELETE `/{id}`.
- GET `/webhooks-event-types`; GET `/webhooks-events` and `/webhooks-events/{id}`; POST `/webhooks-events/{id}/resend`.
- POST `/verify-webhook-signature`; POST `/simulate-event` as a test utility clearly separated from real app events.

App ownership and lookup-specific permissions apply. The SDK does not register webhooks automatically on client construction. [Webhooks reference](https://developer.paypal.com/api/webhooks/v1/)

### 3.3 Tracking and inbound callbacks

Orders tracking is the default for new order integrations. The separate v1 tracking family covers POST/GET-list `/v1/shipping/trackers` and GET/PUT `/v1/shipping/trackers/{id}` for supported transaction-origin use cases. Deprecated POST `/v1/shipping/trackers-batch` is a continuity-only operation, not a recommended new integration path. [Tracking reference](https://developer.paypal.com/api/tracking/v1/)

Order-update callbacks are inbound merchant requests, not outbound POST calls to PayPal. v4 provides callback data parsing/response support and a verification boundary; merchant code computes shipping/tax and chooses whether to accept changes. GET `/v2/checkout/callback/certs/{cert_id}` is a separate outbound certificate operation. A callback verification helper must use the callback-specific documented signing contract, validate trust/expiry and replay context, and have fixtures plus sandbox evidence before being labeled supported; the webhook postback verifier must not be assumed interchangeable. The documentation's illustrative callback path must not become an outbound resource method. [Callback contract](https://developer.paypal.com/api/orders/v2/server-callback), [certificate operation](https://developer.paypal.com/api/orders/v2/certs-get)

### 3.4 Bounded platform support

Include POST `/v2/customer/partner-referrals`, GET `/v2/customer/partner-referrals/{id}`, GET `/v1/customer/partners/{partner_id}/merchant-integrations/{merchant_id}`, and GET the merchant-integrations collection filtered by tracking ID. A redirect return is not proof of onboarding completion. Report payments-receivable status, email confirmation, product/capability status and granted permissions without flattening them into an unsupported universal readiness boolean. [Onboarding contract](https://developer.paypal.com/platforms/seller-onboarding/before-payment)

An explicit seller context supplies seller merchant identity, partner identity and approved delegated authentication. Apply payee and auth assertion only where the endpoint supports them. A mismatched payee/seller/token context fails before submission. Resource origin, operation state and token caches cannot leak across sellers. Merchant-supplied third-party refresh tokens remain distinct from platform client credentials and from merchant-owned app tokens.

Endpoint-supported partner fields such as fees and payee data may be represented faithfully for eligible callers. There is no automatic split-payment strategy, delayed-disbursement workflow, settlement ledger, seller onboarding UI, or permission provisioning service. Revoked permissions produce actionable failures, not a switch to another merchant's credentials.

## 4. Compatibility policy

### 4.1 Classify the complete flow

Each public entry point has a contract record covering argument signature, accepted grouped/leaf fields and values, defaults, result shape, error behavior, resource API/version/origin, supported lifecycle states, consent/callback assumptions, permissions, and evidence. Classify the actual flow, not just its method name:

| Classification | Behavior |
| --- | --- |
| Verified REST-compatible | Explicit migration opt-in may select REST when every declared precondition holds. Preserve the documented legacy contract. |
| Original-backend continuity | Select the original backend before submission when REST equivalence is absent and that backend/credentials remain available. Explain why. |
| Migration required | Explain the required application, consent, credential or object-model change. Do not silently translate into different financial behavior. |
| Unknown/manual review | Origin, dynamic fields, entitlement or contract cannot be established. Do not select a mutating REST route on speculation. |

No migration claim is established by this design review alone. v4 release must demonstrate at least the ordinary new checkout Sale and Authorization flows, supported capture/void/refund servicing, and project REST invoicing lifecycles. These are required compatibility targets, not permission to label all variants compatible. A target that cannot pass its stated evidence gate requires an explicit spec/scope revision before GA, not silent downgrading to continuity.

### 4.2 Surfaces remain distinct

| Surface | Required treatment |
| --- | --- |
| `PayPal.php` | Preserve public merchant signatures, scalar arguments, grouped request arrays and derived response helpers; migration is opt-in. |
| `InvoicingClass.php` | Preserve explicitly project-owned fluent/static methods, data shape and lifecycle behavior within the approved vendor boundary. Do not conflate it with an NVP mapper. |
| `rest/invoice/InvoiceAPI.php` and `InvoiceAPIv2.php` | Inventory/test independently, including templates, QR output and third-party token arguments. Preserve each supported facade envelope such as `RESULT`, `INVOICE`, `INVOICES`. |
| Other REST wrappers and `RestClass.php` | Individually classify checkout, disputes, reporting/sync, payouts/referenced payouts, identity, billing, vault and payment-experience interfaces. Removal is not justified merely by an abandoned upstream dependency. |
| `Adaptive.php` | Keep service-specific XML contracts and eligible original-service continuity. Selected mappings have lower priority; unsupported service replacements are explicit. |
| `PayFlow.php` and other retained public classes | Preserve independent gateway contracts and required inherited helpers. Do not route Payflow through Orders. Inventory other retained classes rather than assuming they are NVP. |

The core package no longer requires `paypal/rest-api-sdk-php`. Project facades use SDK-owned objects internally. Their owned fluent methods, arrays and static entry points remain compatibility targets, but vendor inheritance identity, arbitrary inherited vendor methods, `PayPal\Api\*` objects, `ApiContext`, and vendor-specific `$restCall` injection are not emulated. A caller using those receives a documented replacement using SDK-owned data/configuration/transport; unsupported objects must fail clearly before a network mutation, not at autoload time. Third-party token capability is preserved independently of vendor object identity.

Existing APIs with no verified modern equivalent may be served by a narrow original-endpoint adapter while PayPal still permits that operation. If the provider has withdrawn access, retaining a PHP method cannot restore service; document an unsupported operation with the migration reason. No hidden installation of the old vendor SDK and no blanket assumption that all legacy IDs work on modern endpoints.

Security fixes, telemetry removal, fixed attribution, runtime floor, and removal of accidental debug exits are intentional exceptions to exact historical behavior. `InvoiceAPIv2::CreateInvoice()` must not preserve its `print_r`/`exit` behavior. The existing attribution setter cannot override the fixed partner value: retain an actionable deprecated boundary that accepts the canonical value and rejects other values before a request.

### 4.3 Request and response mapping contract

Mappers accept the real PHP call signature. For example, `GetExpressCheckoutDetails($Token)` and `CreateBillingAgreement($Token)` accept scalars; `GetRecurringPaymentsProfileStatus($ProfileID)` retains its outer `PayPalResult`/`ProfileStatus` response. There is no universal `array $DataArray` dispatcher.

Mapping operates on grouped arrays such as `SECFields`, `Payments`, `Payments[*].order_items`, and `DECPFields`, including the lower-case leaf keys used by callers. Each leaf must be classified as a verified mapping, a local-only control, an explicitly documented harmless omission, or unsupported. Unknown supplied fields default to unsupported; zero and false must not disappear through an empty-value filter. Case normalization follows the entry point's contract and rejects conflicting aliases. No financially meaningful field may be silently dropped; this includes recipient, currency, amount, tax, discount, shipping, capture intent, recurrence, and consent information.

Representative required mapping rules (their full value-domain and omission semantics require contract fixtures):

| Existing input/output | REST interpretation or restriction |
| --- | --- |
| `SECFields.returnurl` / `cancelurl` | Flow-appropriate PayPal experience return/cancel URLs; merchant state and actual callback parameters must be verified. |
| `Payments[n].amt` / `currencycode` | Corresponding purchase-unit amount/currency, with decimal validation and verified purchase-unit/recipient restrictions. |
| `Payments[n].order_items` | Items plus amount breakdown; item totals, quantity, tax and shipping must reconcile without float arithmetic. |
| Payment action `Sale` / `Authorization` | CAPTURE / AUTHORIZE lifecycle; finalization invokes capture or authorize respectively. Unsupported/mixed action variants stay on the original backend or fail preflight. |
| `DECPFields.token` | Resolve the recorded checkout resource in the correct tenant/environment; never manufacture a PayPal-valid EC token. |
| Scalar token/profile arguments | Normalize per method, preserving the public signature and resource-origin requirement. |
| `TOKEN` / `REDIRECTURL` | Document operation-specific token meaning; return the actual provider approval link. A local handle is lookup-only, never a valid Classic redirect token. |
| `PAYMENTINFO_n_TRANSACTIONID` | Actual capture or authorization ID for that payment action; never substitute the order ID. |
| `REFUNDTRANSACTIONID`, fee/amount/status fields | Use corresponding provider values and explicitly verified status translations; unavailable fields follow documented optionality, never fabricated defaults. |
| `ERRORS`, `ORDERITEMS`, `PAYMENTS` | Preserve helper-derived shapes with method-specific fixtures, including empty/warning/failure cases. |
| `REQUESTDATA`, `RAWREQUEST`, `RAWRESPONSE` | Preserve documented container/key types where required, but sanitize secrets/card data/PII; they are diagnostics, not guaranteed byte-for-byte wire transcripts. |
| REST invoice facade arrays such as `merchantInfo`, `billingInfo` | Map through that facade's own contract; do not use Adaptive's `CreateInvoiceFields` mapping. |

If a required legacy response value cannot be obtained truthfully, the flow is not seamless-compatible. Do not synthesize NVP API version, timestamp, correlation ID, successful ACK, error numbers or fees merely to fill expected keys. Local errors use namespaced SDK codes and a documented failure shape for each adapter; provider details remain separately identifiable. Original-backend operations preserve real original-backend results.

### 4.4 Resource and lifecycle rules

New Express Checkout migration uses REST Orders only for verified request shapes and approval flows. Merchant code must consume the returned approval URL. A merchant constructing `cgi-bin/webscr` URLs needs an explicit migration change and cannot be classified seamless. Validate returned identifiers against durable checkout state and merchant session ownership. A payer ID alone is not payment authorization.

`DoExpressCheckoutPayment` validates permitted changes before finalization. Supported amount/shipping changes update the order under its allowed lifecycle; unsupported changes fail without capturing. Once a REST order exists, later unsupported inputs do not restart it on Classic. Buyer-action-required and pending outcomes remain visible and must not trigger fulfillment.

Historical transactions, in-flight Classic checkouts, existing recurring profiles, existing billing agreements and invoices keep their recorded backend/API version unless operation-specific interoperability is proven. Unknown origin requires an explicit origin declaration or trusted stored provenance; an ID prefix or a failed request is insufficient. Reads used for reconciliation do not authorize mutations on a guessed backend.

New recurring billing uses Products/Plans/Subscriptions with schedule and pricing known before buyer approval. Trial duration, initial/setup charges, start date, outstanding balance and pending/active state must be modeled. Old recurring profiles continue on their original servicing API. No automatic cancel/recreate or double billing while migrating; merchant-controlled migration requires explicit new consent where needed and a documented cutoff between old and new collection.

Reference transactions and direct cards require verified vault/consent, account eligibility, payment-source rules and handling of buyer action. Old agreement IDs do not become vault tokens. `ManagePendingTransactionStatus`, `UpdateAuthorization`, billing agreement updates and similar methods stay on the original backend unless equivalent semantics are established; “wait for capture” or “void” is not a substitute for accept/deny.

MassPay compatibility must distinguish submission acceptance from per-recipient outcomes. It cannot imply that either MassPay or Payouts settles recipients synchronously. Reporting results may lag activity by up to three hours; transaction search cannot stand in for a guaranteed immediate capture lookup. [Reporting schema and latency](https://github.com/paypal/paypal-rest-api-specifications/blob/90e8041ffe02d80c452d2b476bedd59a8d219bdc/openapi/reporting_transactions_v1.json)

## 5. Routing, durable state and payment safety

### 5.1 Route selection

With migration disabled, Classic entry points use Classic. With migration enabled (`upgrade_from_classic = true` remains the opt-in entry point), preflight combines the declared flow profile, normalized fields, credentials/capabilities and known resource origin. It chooses verified REST or original-backend continuity before any mutation. Missing original-backend credentials produce an actionable compatibility failure, not an attempted REST substitute.

The initial release's checkout compatibility profiles cover a single merchant/payee and a single payment with Sale or Authorization, supported item/amount/shipping fields, and consumption of returned approval links. Profiles declare their supported follow-up calls and output requirements before the first remote step. Multi-payee/split-payment, billing-agreement/recurring additions, custom Classic URL construction and unverified field combinations do not inherit that classification. Adding a proven profile is additive; broadening a profile requires new evidence. The opt-in flag is not a waiver of these preconditions.

Expose route/reason inspection and sanitized per-operation diagnostics. Constructor notices can describe configured limitations but cannot predict all dynamic calls. In-flight route decisions are immutable. Turning migration off affects new flows; it must not redirect existing REST resources to Classic.

### 5.2 Separate storage contracts

OAuth tokens are expiring credentials. The default token cache can be in-memory, with an injectable shared cache. Read provider `expires_in`, apply a safety margin, and coordinate refreshes where shared caching supports it. Never use a fixed universal token lifetime.

Migration/resource records and operation records are durable. Required keys include tenant, environment, merchant/seller, app/auth context, API family/version and resource type. Credentials themselves are not stored in migration records. Records retain actual provider IDs, local correlations, origin and mapping version. They remain usable across processes, deploys and rollback of new-flow routing.

Production flows spanning requests must be configured with durable storage supporting atomic creation, uniqueness and compare-and-set or equivalent transactions. In-memory migration storage is test-only and must be explicitly labeled; it cannot be silently selected in production. Missing/unavailable required storage blocks a new mutation before it is sent. The SDK owns the storage contract; merchants own deployment, backups and retention administration. A documented durable integration must accompany release.

This requirement applies to SDK-managed migration orchestration. Native stateless resource calls remain usable without installing a migration store; native callers own durable operation IDs, resource associations and retry coordination across application requests. A per-call generated request ID protects only eligible retries of that call, not independent repeated invocations. Legacy same-backend calls do not gain automatic cross-request deduplication merely by upgrading the package.

Operation records bind a stable logical operation ID to backend, target, action, canonical request fingerprint and provider idempotency key. Same ID plus different financial inputs fails. Parallel same-ID submissions permit one active sender; followers return an existing outcome or a documented in-progress result. An expired lock alone never proves that a request was unsent.

States distinguish prepared, submitted/in-progress, awaiting buyer action, outcome unknown, succeeded, and definitively failed. A process crash between sending and recording the response must leave recoverable uncertainty. Multi-call flows retain each completed remote step; a failed later step does not erase a created order/product/plan or silently repeat a completed charge.

Completed operation records default to at least 30 days retention and must not expire before the applicable provider idempotency window or declared merchant retry window. Unknown/in-progress records are not automatically purged. Resource-origin mappings remain for the resource's servicing lifetime; a checkout approval expiry does not delete transaction provenance. Operator cleanup requires reconciliation and retained merchant evidence. These records are not a promise of provider deduplication after PayPal's own window expires.

### 5.3 Retries and uncertainty

Retry support belongs to each operation's documented semantics. Safe reads may retry transient transport errors, rate limits and server errors. Mutation retries require documented provider idempotency/deduplication support, the same operation/key/payload, and an unexpired provider window. Merely supplying `PayPal-Request-Id` is not proof of support. [PayPal idempotency guidance](https://developer.paypal.com/api/rest/reference/idempotency/)

Default eligible retries are bounded to two retries after the initial attempt, exponential backoff with jitter, and the caller's total deadline. Respect `Retry-After` where applicable; if it exceeds the deadline, return control instead of sleeping indefinitely. Do not retry business validation/permission/decline failures as transport errors. Token refresh after an explicit authentication failure is bounded and must not bypass mutation retry safety.

A timeout, unreadable response, ambiguous server failure or interrupted sender can mean that a payment succeeded remotely. Report an unknown outcome with operation ID and provider debug ID only if actually received. Reconcile using the original resource/backend and supported idempotent recovery. Never replay through Classic, use a new key to “try again,” or claim a failed payment merely because the response was lost. Do not promise exactly-once processing for unrelated operation IDs or merchant submissions outside this storage contract.

## 6. Native PHP and transport contracts

Native resource methods accept documented request data and a consistent optional request-options object. Options include idempotency key, supported `Prefer` value, timeout/deadline and explicit seller/token context. Protected authentication, environment and fixed attribution cannot be overridden through arbitrary headers. Reject options unsupported by a given operation when accepting them would imply semantics that PayPal does not provide.

Requests follow provider JSON names and preserve false, zero, empty collections and explicit null where permitted. Omitted fields remain distinct from null. Decimal money uses strings and currency-specific precision; no binary floating-point rounding of payment amounts. Unknown native request fields may pass through as JSON-compatible provider extensions; adapters use the stricter mapping policy in section 4 because translation can discard meaning.

Native responses are immutable typed objects with lossless array/JSON access and read-only `ArrayAccess`. Known nested shapes receive typed access; unknown fields and statuses remain available without decoding failures. An unknown status is never success by default. Convenience accessors return optional values where absent and cannot arbitrarily choose the first capture from a multi-payment result.

Response metadata includes HTTP status, headers/links, request ID and provider debug ID where present, separate from business fields. Collection responses preserve pagination, totals when provided and continuation links; single calls do not secretly fetch every page. Optional iteration is explicit, bounded and validates link destination/context before sending credentials. Empty/204 responses return an empty response with metadata. Binary and text responses expose content type and bytes/stream rather than forcing JSON; uploads support documented multipart fields and caller-owned streams.

Native failures distinguish local validation/configuration, authentication, authorization, API business errors, rate limits, transport failures and unknown mutation outcomes. API exceptions preserve status, name, details and debug ID when returned. A successful HTTP response does not imply settled money; expose pending and buyer-action states. Legacy adapters translate only according to their own documented envelopes.

Provide an injectable transport with a default cURL implementation and no mandatory second HTTP client. Use connection and total timeouts, TLS peer/hostname verification, a usable CA configuration and trusted environment endpoints. No silent downgrade or automatic forwarding of credentials to arbitrary links/redirects. Optional transports must pass the same transport contract tests. Client construction performs no payment, onboarding, webhook registration or other remote mutation.

## 7. Browser, notifications and fulfillment

Support server-created Orders and both modern JS SDK checkout and server-only redirect checkout. Server endpoints own price, currency, merchant identity and order association. Examples must not accept client totals as authoritative or fulfill merely because the browser reached a success URL.

Provide server support and examples for JS SDK v6 PayPal, cards/3DS, wallets, Pay Later, Venmo, Fastlane, vaulting and subscriptions where eligible. The merchant owns rendering, domain registration, client eligibility checks, buyer interactions, CSP and application endpoints. Keep frontend API examples version-consistent; do not mix v5 and v6 on one page. The PHP SDK does not promise that every payment method is available to every merchant/browser/country.

Browser-safe token generation is a distinct OAuth operation/configuration from server access-token acquisition. Current v6 setup uses a client ID for normal initialization and a browser-safe client token for Fastlane; consult the current flow-specific contract for additional uses. Tokens are cached by purpose, app, environment and allowed domains/subject, never substituted across contexts. The SDK must never expose a server access token or client secret to the browser. [Current JS SDK setup](https://developer.paypal.com/sdk/js/set-up/)

Webhook verification defaults to PayPal's verification API, using the configured webhook ID for the correct app/environment and the received headers/raw event content. Preserve the event content without lossy parsing/reformatting. Invalid signatures, malformed messages and unavailable verification produce distinct outcomes; inability to verify never means valid. Merchant code durably accepts verified events before acknowledging/fulfilling and deduplicates by app/environment/event ID. [Verification contract](https://developer.paypal.com/api/rest/webhooks/rest/)

Examples must handle duplicate and out-of-order events, correlate the actual payment resource and merchant, and reconcile uncertain state rather than overwriting a newer state blindly. Fulfillment requires verified authoritative payment state and application-level idempotency. Keep IPN-dependent original-backend integrations working; REST webhooks do not silently replace their subscriptions or IPN handlers. Mixed migration examples prevent both notification systems from fulfilling the same purchase twice.

Simulator events do not prove production verification: PayPal's postback verification API cannot verify simulator mock events. Use actual sandbox app events for verifier acceptance. Callback verification and webhook verification are distinct contracts. [Simulator limitations](https://developer.paypal.com/api/rest/webhooks/simulator/)

## 8. Security, packaging and migration experience

TLS verification applies to all retained transports, including Classic, Adaptive and Payflow. Secure CA/proxy configuration is supported; disabling verification is not a production compatibility feature. Remove outbound telemetry and embedded tracking credentials. Preserve or refactor inherited helper behavior needed by Payflow while removing telemetry side effects; deletion of `TPV_Parse_Request()` without accounting for its caller is not acceptable.

Default logging contains operation/route, timings, safe status and correlation fields. Never log credentials, auth headers, access/refresh/client tokens, PAN/CVV or unredacted request bodies. Sanitize both normal and exception paths, including XML/NVP, multipart and legacy raw diagnostic fields. Debug output is opt-in and still sanitized; unknown fields default to omission from logs. Monetary/API data required by the business response remains available to the caller and is not automatically sent to a logger.

`WekoodoLLC_Ecom` is fixed in one source of truth and applied through the provider-supported attribution mechanism for REST, retained protocols and browser examples. No generic options or old setters may override it. Attribution is documented library behavior, not an additional telemetry service.

PHP dependency constraint is `^8.3`; required extensions and runtime libraries must be declared and installable without development dependencies. The release CI matrix covers PHP 8.3 through the latest supported stable PHP version at release. As reviewed, 8.2 security support ends 2026-12-31 and 8.3 ends 2027-12-31. SDK support cannot extend PHP upstream security support. [PHP support schedule](https://www.php.net/supported-versions.php)

Preserve existing namespace/class autoload compatibility while adding native namespaces without case-sensitive filesystem collisions with the existing `rest/` tree. Remove the abandoned vendor dependency only when all retained project-owned classes load without it. Document migration from vendor objects, configuration and custom call injection, including the direct-vendor boundary accepted in section 4.

The main package ships `vendor/bin/paypal-upgrade-check` with `nikic/php-parser` in runtime requirements, so a downstream `composer install --no-dev` can run it. The checker emits human-readable and machine-readable results with file/line, identified surface, classification, reasons, unresolved inputs and next action. It does not read secrets or execute payment calls. Dynamic dispatch, unknown array values, ambiguous resource IDs and inaccessible dependencies produce manual-review results. Separate detected compatibility from unverified runtime eligibility; credentials in source are not proof of access.

CLI exit codes: 0 means analysis completed with no detected blockers or unknowns, not that live migration is certified; 1 means findings require migration/manual review; 2 means analysis could not complete. Report parse failures and analyzed/excluded files so a partial scan cannot look complete.

The new Composer package and the old package must not co-install because they export the same classes. v4 declares a conflict with `angelleye/paypal-php-library`; it does not claim to replace all historical versions or automatically satisfy downstream old-name constraints. Migration instructions remove the old direct dependency and require the new one, and explain that dependent packages may need their constraints updated. Validate clean installs and realistic dependency conflicts. A Packagist rename/abandonment notice does not itself upgrade an application.

Retain GPL-3.0-or-later licensing and historical contributor attribution. Coordinate the Wekoodo repository/package cutover with release; publishing, repository transfer, Packagist changes and external issue changes are separate actions, not authorized by this spec-writing session. v3 receives security-only maintenance for 12 months after v4 GA. Merchants remaining on unsupported PHP or withdrawn provider APIs must address those separately. v4 minor releases preserve the established PHP API contracts and keep the 8.3 floor for the major line; runtime-policy changes require a separately approved versioning decision.

## 9. Release acceptance evidence

Acceptance is based on contract and lifecycle evidence, not a mapper count or a line-coverage percentage. Required evidence is recorded per operation/surface; fixtures containing sensitive data are sanitized without changing the tested semantics.

| Acceptance area | Required evidence |
| --- | --- |
| Operation coverage | Every baseline operation in section 3 is implemented, documented and contract-tested for HTTP method/path/auth, options, payload and result/error shape. Conditional and continuity-only operations are labeled separately. |
| Core contracts | Tests for lossless unknown fields/statuses, null/omission/zero, decimal precision, pagination, 204, binary/text responses, multipart, protected headers, tenant isolation and transport failures. |
| Classic compatibility | Fixtures for real grouped arrays and scalar arguments; Sale and Authorization checkout with buyer approval; allowed and disallowed finalization changes; capture/void/refund; derived arrays, warnings, failures and truthful optional fields. |
| Existing REST compatibility | Separate fixtures/lifecycles for `InvoicingClass`, `InvoiceAPI`, `InvoiceAPIv2`, and every other retained facade. Vendor-free autoload plus explicit vendor-object migration diagnostics. |
| Invoicing | Draft/create/edit/send/remind/cancel, external payment/refund bookkeeping, deletion restrictions, templates and QR; contract coverage and eligible sandbox scenarios for recurring series/rules/reminders. Historical invoice origin tested separately. |
| Durable routing | Cross-process checkout, concurrent duplicate submission, changed payload with reused ID, crash before/after send, lost response, store outage, expired lock, retention and switching migration off while REST resources remain. No uncertain payment is replayed on another backend. |
| Historical continuity | In-flight Classic checkout, old transaction servicing, recurring profiles, billing agreements, invoices and Payflow helper dependencies; unsupported/unknown origin behavior. |
| Recurring/vault/cards/payouts | Approval/pending/active transitions, trials and initial charges, consent required, buyer action/declines, partial outcomes and accepted-versus-settled distinction; no automatic cancellation or double collection. |
| Platform | Referral plus seller status, granted/revoked permission handling, payee/context mismatch and cross-seller token/resource isolation. |
| Notifications | Actual sandbox app signature verification, invalid/unavailable verification, duplicates/out-of-order delivery, callback-specific verification evidence and IPN/webhook fulfillment deduplication. |
| Security | All retained transports verify TLS; sensitive-data fixtures verify redaction; no telemetry; fixed attribution; no debug exits; no credential leakage through redirects/pagination. |
| Packaging and runtime | Fresh downstream `--no-dev` install and runnable CLI; PHP matrix; old/new package conflict; namespace/autoload integrity; no undeclared vendor dependency. |

Every merchant family needs an eligible sandbox lifecycle demonstration with relevant buyer approval and resource setup/cleanup. Entitlement-dependent gaps name the missing capability, operation and consequence. GA cannot advertise a seamless-compatible flow without its sandbox evidence. Other operation-level gaps require explicit release review and an accurately limited support statement; they cannot be hidden by marking skipped tests as successful. This spec does not claim any sandbox verification was performed during brainstorming.

Examples cover native browser checkout, server redirect checkout, migration checkout, REST invoicing, subscriptions/vault consent, webhook verification/fulfillment, and delegated seller context. Documentation must state required merchant application changes and distinguish provider acceptance, authorization, capture, settlement and fulfillment.

The final release review checks each normative requirement and the compatibility matrix against evidence. The old 52-plan checklist and its issue statuses are not release acceptance criteria.

## 10. Evidence precedence and next-session boundary

Project source at baseline commit `89187eea882c660213053ced1a271a77036d4d06` establishes existing call contracts. The handoff records source-level findings, not runtime proof. Current official endpoint documentation takes precedence over a stale schema or generalized migration rule; contradictions remain explicitly recorded until resolved by the endpoint contract and testing.

PayPal guidance used in this design:

- [AI-Toolkit routing skill](https://github.com/paypal/AI-Toolkit/blob/310b4962f0e5b3d09ff1c909d96ad5e226703ecb/skills/paypal-routing/SKILL.md) and [best-practices skill](https://github.com/paypal/AI-Toolkit/blob/310b4962f0e5b3d09ff1c909d96ad5e226703ecb/skills/paypal-best-practices/SKILL.md), with authentication and JS SDK v6 references re-read in this session. Earlier invoicing/subscription/webhook/card/payout reference review is recorded in the handoff. These guides were read, not installed.
- [RulesHub migration rules](https://github.com/paypal/ruleshub/blob/7c20ddb0bc8be79ccc6b2997957cd1ac8e0ab82d/upgrade-nvp-soap-to-rest/rules.md) are mapping inputs from the preceding review, not proof of this library's facade compatibility.
- The pinned API schemas omit live-reference features including subscription listing, newer invoicing groups, reporting summaries and callback certificates. The inventory above includes those current documented additions and isolates schema-only accounting-sync reads.
- The pinned v6 skill and current setup page differ on browser-token use. Follow current flow-specific documentation; never infer token purpose from its JSON field name alone.

No implementation plan, SDK code, test implementation, API mutation, external issue update or release action is part of this deliverable. The next session begins from this specification after final user review, with fresh implementation planning. It must not execute the superseded plan files.
