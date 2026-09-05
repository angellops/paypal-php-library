# v4 SDK modernization — brainstorming handoff

> **Historical snapshot.** The replacement spec has since been written and the original PRD/plans archived. Start with [current state](current-state.md) for the active artifact and review boundary. The continuation instructions and paths below describe the earlier session.

Date: 2026-09-04
Status: Brainstorming in progress; architecture approach approved; replacement spec not yet written.

## Start here

Continue the architectural brainstorming session using `.agents/skills/brainstorming/SKILL.md`. The user requested this handoff to move to a fresh context window. Do not restart the review or ask them to reconfirm approved decisions.

The eventual deliverable is a fresh design/spec replacing the authority of `docs/PRD.md` and `docs/v4-plans/`. **Stop after the spec is written and reviewed. Do not create an implementation plan or implement SDK changes.** The user will open a separate session for implementation planning. This explicit instruction overrides the brainstorming skill's normal transition to writing-plans.

The previous session reviewed the PRD, the plan index and all 52 phase files, examined SDK public contracts and representative implementations/demos, and consulted current official PayPal documentation. This was a source/document review, not a runtime or sandbox validation. No SDK changes or tests were performed.

## Approved decisions — do not reopen without a concrete reason

1. **Compatibility:** Promise seamless REST migration only for verified compatible flows. Preserve Classic continuity for incompatible flows and document what an actual migration requires. User selected: “Yes—verified compatibility, with Classic continuity.”
2. **Coverage:** Comprehensive merchant REST coverage in v4, with explicitly bounded partner/platform support. User said to go with this recommendation. A smaller checkout-only release was not selected. Exact operation and platform boundaries remain to be specified.
3. **Architecture:** An SDK-owned REST core with separate compatibility adapters. User's latest approval: “Yes, proceed with your rec.” One transport/authentication/resource layer, separate adapters for Classic NVP, existing REST integrations, and selected lower-priority Adaptive integrations. Alternatives considered were wrapping PayPal's current PHP Server SDK and generating the whole client from schemas. Neither was selected. Schemas remain useful validation/reference inputs.
4. **Invoicing separation:** Existing REST invoicing, including `InvoicingClass.php`, must remain distinct from the older invoicing methods housed in `Adaptive.php`. Similar operation names do not make them the same API or integration contract.
5. **Adaptive priority:** It had relatively few users. Seamless migration is desirable where verifiable, but lower priority. Do not make Adaptive migration the focus or infer that its methods represent all existing invoicing integrations.
6. **PayPal guidance:** User supplied `https://github.com/paypal/AI-Toolkit/tree/main/skills` and wants its skills used to help cover current APIs/features. Skills and relevant references were fetched/read, not installed into the project or user environment.

## User intent and interaction preferences

- Modernize this longstanding PHP library to support PayPal's latest merchant REST integrations while minimizing changes for existing users.
- Request and response field mapping is a central requirement.
- Revisit the old PRD and plans through a fresh brainstorming process, then produce a replacement spec. Existing documents are historical inputs, not decisions to blindly carry forward.
- Ask substantive questions one at a time and present design sections for discussion. Keep explanations concrete; avoid repeatedly recapping the whole audit.
- No pending unanswered question remains from the prior session: the last architecture question is now approved.
- The current request authorizes writing this handoff, not accelerating to the final spec before the remaining brainstorming.

## Workspace baseline

- Repository: `/home/angellops/projects/paypal-sdk-php`
- Branch: `main`
- HEAD: `89187eea882c660213053ced1a271a77036d4d06`
- HEAD description: `docs: v4.0 prep — PRD revision, 52 plan files, GitHub issues (#285)`
- Pre-existing untracked file: `skills-lock.json`. Do not overwrite or include it in unrelated work.
- Before this handoff, no files were modified by the session.
- No applicable `AGENTS.md` was found in the repository or ancestor paths checked.
- Installed local process skills live under `.agents/skills/`.
- `docs/PRD.md` has 631 lines. `docs/v4-plans/README.md` indexes 52 plans across phases 0–7 and references existing GitHub issues #286–#337 under milestone 12. These issue references were read locally; their live status was not audited or changed.

## Important correction from the user

The assistant initially summarized “Classic invoicing lives in Adaptive.php” too broadly. The narrow code finding was real: the old plan puts hooks for `CreateInvoice`, `SendInvoice`, etc. on `PayPal.php`, whereas those particular Classic/XML methods are in `Adaptive.php`. But the project ALSO has a distinct REST invoicing stack, and the user explicitly corrected the conflation.

Treat these as independent compatibility surfaces:

| Surface | Evidence in the repository | Migration implication |
| --- | --- | --- |
| Classic merchant NVP | `src/angelleye/PayPal/PayPal.php` | Existing grouped request arrays, scalar arguments, NVP fields and derived helpers |
| Older service APIs in Adaptive | `src/angelleye/PayPal/Adaptive.php` | XML calls to services including `Invoice`; response keys such as `Ack`, `Errors`, `InvoiceID` |
| Existing REST invoicing model | `src/angelleye/PayPal/InvoicingClass.php` | Extends old vendor `PayPal\Api\Invoice` but directly calls `/v2/invoicing/...`; own object/fluent/static method contracts |
| Existing REST invoicing facades | `src/angelleye/PayPal/rest/invoice/InvoiceAPI.php` and `InvoiceAPIv2.php` | Separate wrapper contracts; samples commonly instantiate `InvoiceAPI`; results include `RESULT`, `INVOICE`, `INVOICES` |
| Other existing REST integrations | `RestClass.php`, `CheckoutOrdersClass.php`, disputes/reporting/etc. wrappers and `rest/` subtree | Need explicit migration treatment, not a blanket “dead code” designation |
| Payflow | `src/angelleye/PayPal/PayFlow.php` | Separate gateway contract and inherited helper dependencies |

`InvoiceAPIv2::CreateInvoice()` also contains `print_r($invoice); exit;` before its request. This is source evidence of unfinished/debug behavior in that path, not proof that every REST wrapper is unused or broken. Do not preserve debug output as an intended compatibility contract.

## Review findings to incorporate into the design

### Compatibility must be defined by flow, fields, and resource origin

- A method name having a REST counterpart does not establish transparent compatibility. New consent, merchant entitlements, callback changes, differing statuses, missing data, and historical resource IDs all matter.
- The old mapper interface takes `array $DataArray` universally. Actual scalar methods include `GetExpressCheckoutDetails($Token)`, `CreateBillingAgreement($Token)`, and `GetRecurringPaymentsProfileStatus($ProfileID)`.
- Requests are often grouped arrays with lowercase leaf keys, e.g. `SECFields`, `Payments`, `order_items`, `DECPFields`; mapping raw uppercase NVP field names alone will not cover the library interface.
- Existing results include `ERRORS`, `ORDERITEMS`, `PAYMENTS`, `REQUESTDATA`, `RAWREQUEST`, and `RAWRESPONSE`, beyond the headline payment fields. `GetRecurringPaymentsProfileStatus()` returns an outer `PayPalResult` / `ProfileStatus` array.
- Inventory and test each class/entry point independently. Do not force NVP, XML, and existing REST wrapper responses into one envelope.
- Decide what compatibility means for direct use of old vendor SDK models, inherited methods, `ApiContext`, custom rest-call injection, fluent APIs, and third-party tokens. Project-owned facade compatibility must not accidentally promise complete emulation of the abandoned vendor SDK.
- Unsupported financially meaningful fields must not be silently dropped. Response values, success states, fees, and error codes must not be fabricated just to match a shape.
- Explicit policies are needed for historical transactions, existing recurring profiles, existing billing agreements, existing invoices, and in-flight checkout sessions. Cross-API ID interoperability has NOT been established by this review. Do not assume universal compatibility or universal incompatibility; classify and verify the specific operation.

### Routing, persistent state, and failure behavior

- Proposed requirement, not yet approved in detail: select the backend before a mutating operation, using verified capability and known resource origin. Never replay an uncertain REST payment through Classic after a timeout or error.
- Separate expiring OAuth token caching from durable migration/resource mappings and operation state. The old typed `AccessToken` store cannot also serve arbitrary checkout/profile/product mappings as specified.
- Tenant/merchant/environment isolation, expiry, concurrency, duplicate submission handling, and rollback behavior need explicit contracts.
- The old synthetic `EC-...` bridge cannot make a fabricated token valid at PayPal. It may help local lookup, but applications constructing their own Classic redirect URL remain a migration concern. Prefer returned PayPal approval links, and verify return/cancel parameters rather than assuming rewrites survive.
- The bundled Classic demo saves the token in session and redirects using returned `REDIRECTURL`; that proves less than compatibility with all merchant redirect/callback patterns.
- Retain enough completed-operation information for retries and reconciliation; immediate mapping invalidation after success can undermine that.

### Specific mappings needing revision

- `DoExpressCheckoutPayment` must distinguish Sale and Authorization and handle permitted order changes before finalization; the old plan simply captures.
- Phase 3.4 proposes `POST /v2/payments/authorizations/{auth_id}` for authorization creation. The current Payments API does not list that operation; Orders authorization has its own lifecycle.
- Phase 3.8 proposes `PUT /v3/vault/payment-tokens/{token_id}` for billing agreement updates. It is absent from the current documented Vault surface.
- `ManagePendingTransactionStatus` acceptance/denial cannot be represented as “let capture complete” or “void” without establishing equivalent semantics.
- Recurring profile creation requires more than product → plan → subscription calls: schedule availability before consent, buyer approval, active/pending states, initial charges, trial semantics, and continued servicing of old profiles must be designed.
- Reference transactions and direct cards need explicit vault/consent/merchant eligibility and buyer-action handling. A JSON field map does not eliminate those requirements.
- Reporting is not a guaranteed immediate substitute for operational lookup; PayPal documents transaction reporting latency up to three hours.
- The old MassPay plan claims it was synchronous. That assertion was flagged as suspect but not fully verified from primary documentation. Do not carry it forward; distinguish submission acceptance from recipient settlement for each API.

### Modern REST coverage and SDK contracts

- Replace the claim of “full REST coverage” by an explicit resource/operation inventory, version, scope, entitlement assumptions, and verification status.
- Candidate merchant coverage: Orders, Payments, Vault, Products/Plans/Subscriptions, Invoicing including templates and lifecycle operations, Payouts, Disputes, Webhook management/events/verification, Reporting, Identity, and modern checkout server support.
- Orders coverage must consider confirm-payment-source and tracking. Current documentation also describes order-update callbacks; these are merchant inbound flows, not ordinary outbound resource operations.
- Old plans omit operations such as subscription revision and various invoice/webhook/dispute capabilities already relevant to existing wrappers.
- JS SDK v6 guidance adds client-token generation where required, eligibility, card fields, wallets, Pay Later, Venmo, Fastlane, vaulting, and subscriptions. Distinguish server support from the merchant's frontend responsibilities. Keep supported server-only redirect flows.
- Bound platform support precisely: referral creation alone does not establish seller onboarding completion. Seller status, delegated authentication, payee, permissions, and token/cache isolation need consideration. Full marketplace orchestration has not been approved.
- Specify pagination/metadata, empty responses, binary responses, multipart uploads, decimal-money handling, unknown fields/statuses, typed DTO semantics, and consistent request options.
- Decide retries per operation and idempotency support, with preserved keys and bounded backoff. A blanket retry rule or a supplied header alone is not a complete payment-safety contract.

### Cleanup, runtime, packaging, and tests

- `composer.json` still requires `paypal/rest-api-sdk-php: "*"`. The old PRD's claim that all wrappers fatal because the dependency is absent contradicts the repository. Removing the dependency is desirable but requires a migration strategy for its consumers.
- `PayFlow::ProcessTransaction()` calls inherited `TPV_Parse_Request()` (around line 199). Deleting the method while following the old “PayFlow unchanged” instruction would break it.
- TLS verification is disabled in multiple legacy transports, not only `PayPal.php`. Security cleanup needs scoped compatibility treatment across retained classes.
- Runtime floor is unresolved. The old PHP 8.1 floor is outdated: as checked on 2026-09-04, PHP's supported table starts at 8.2, with 8.2 security support ending December 2026. Consider proposing 8.3 minimum, but the user has not approved that choice.
- The upgrade CLI's `nikic/php-parser` dependency is in `require-dev` in the old plan; downstream package users do not receive it. Decide packaging/runtime dependency treatment.
- Static analysis cannot reliably know runtime credentials, account entitlements, arbitrary dynamic calls, or all field values. The CLI needs an honest unknown/manual-review result instead of declaring everything cleanly upgradable by method name.
- Preserve IPN-dependent legacy operation while documenting webhooks for REST. Define duplicate/out-of-order notifications and fulfillment rules in examples.
- Logs/raw diagnostics need explicit secret/card/PII handling. Do not promise a provider debug ID where no HTTP response exists.
- Release confidence needs contract fixtures and lifecycle/error tests across claimed compatibility, not only checkout demos or an 80% line threshold.
- Simulator webhooks cannot be verified by the PayPal postback signature API. Test that path with actual sandbox app events.
- Sandbox scenarios require buyer approval, capabilities, appropriate state and cleanup. Do not label skipped entitlement-dependent tests as passes.
- Old documents conflict on mapper counts (30/32), source paths, immutable config/setters, array/null dispatch, async roadmap, and fallback details. Rewrite the spec coherently rather than patching those details piecemeal.

## Existing PRD decisions to carry forward provisionally

These are documented prior-session choices, not newly reconfirmed business decisions. Preserve unless contradicted or worth explicitly revisiting:

- Wekoodo rebrand; canonical package planned as `wekoodo/paypal-php-library`.
- Preserve PHP namespace `angelleye\PayPal` to reduce migration work.
- Hardcoded partner attribution `WekoodoLLC_Ecom`, with one source of truth, not a merchant config knob. The user had explicitly insisted on this in the prior PRD history.
- Remove the old outbound telemetry; prior PRD records maintainer confirmation that the external endpoint/key was already decommissioned. Do not reproduce the embedded key in new documents.
- GPL license continuity.
- Synchronous PHP SDK, merchant-owned webhook endpoint and frontend.
- Existing PRD proposed API-based webhook verification, v3 security support for 12 months, and coordinated repository/package cutover. Detailed support/release commitments remain to be reconciled in the new spec.
- Package rename needs real dependency/co-installation/migration treatment; changing Packagist branding does not itself upgrade installed applications.

## PayPal skills and evidence already consulted

Use pinned links for reproducibility and current docs to resolve changes. These remote skills were read as guidance; they are not installed locally.

**AI-Toolkit revision:** `310b4962f0e5b3d09ff1c909d96ad5e226703ecb`

- [paypal-routing](https://github.com/paypal/AI-Toolkit/blob/310b4962f0e5b3d09ff1c909d96ad5e226703ecb/skills/paypal-routing/SKILL.md)
- [paypal-best-practices](https://github.com/paypal/AI-Toolkit/blob/310b4962f0e5b3d09ff1c909d96ad5e226703ecb/skills/paypal-best-practices/SKILL.md)
- Read under `paypal-best-practices/references/`: `authentication.md`, `invoicing.md`, `subscriptions.md`, `webhooks.md`, `js-sdk-v6.md`, `disputes-refunds.md`, `payouts.md`, `3d-secure.md`.

**RulesHub revision:** `7c20ddb0bc8be79ccc6b2997957cd1ac8e0ab82d`

- [NVP/SOAP migration rules](https://github.com/paypal/ruleshub/blob/7c20ddb0bc8be79ccc6b2997957cd1ac8e0ab82d/upgrade-nvp-soap-to-rest/rules.md)
- Read mappings in that directory: `SetExpressCheckout.json`, `GetExpressCheckoutDetails.json`, `DoExpressCheckoutPayment.json`, `CreateBillingAgreement.json`, `GetTransactionDetails.json`.
- Mapping files distinguish `supported`, `not_supported`, value transformations, and flow-specific augmentations. They are useful inputs to a project compatibility matrix, not proof that this SDK's complete merchant flow is interchangeable.
- Apply reference material in context: it includes merchant-app-specific instructions that are not appropriate defaults for a general library, and generalized rules that need endpoint validation. Examples: fixed OAuth lifetimes versus reading `expires_in`, blanket POST idempotency/retries, simulator versus postback verification, and a `TOKEN` mapping whose meaning depends on operation. The user's Classic continuity requirement takes precedence over new-integration guidance to avoid all legacy APIs.

Useful official sources:

- [NVP/SOAP status](https://developer.paypal.com/api/nvp-soap/): legacy, still supported; do not assume a shutdown date.
- [Orders v2](https://developer.paypal.com/api/orders/v2)
- [Payments v2](https://developer.paypal.com/api/payments/v2)
- [Payment Method Tokens v3](https://developer.paypal.com/api/payment-tokens/v3)
- [JS SDK v6 setup](https://developer.paypal.com/sdk/js/set-up/)
- [Webhook simulator limitations](https://developer.paypal.com/api/rest/webhooks/simulator/)
- [Webhook integration and verification](https://developer.paypal.com/api/rest/webhooks/rest/)
- [Idempotency](https://developer.paypal.com/api/rest/reference/idempotency/)
- [Reporting schema and latency](https://github.com/paypal/paypal-rest-api-specifications/blob/main/openapi/reporting_transactions_v1.json)
- [PayPal OpenAPI specifications](https://github.com/paypal/paypal-rest-api-specifications/tree/main/openapi)
- [Merchant/platform vault onboarding](https://developer.paypal.com/platforms/checkout/save-payment-methods/onboarding/platform/)
- [Current PHP Server SDK](https://github.com/paypal/PayPal-PHP-Server-SDK): consulted as an alternative; its README listed five controller families at review time.
- [PHP support lifetimes](https://www.php.net/supported-versions.php)

## Suggested continuation

1. Briefly acknowledge the architecture is approved and continue with the next design section. Do not repeat the entire audit.
2. Present the proposed compatibility policy for existing NVP and REST integrations: what entry points remain, unsupported-field behavior, historical-resource routing, and what happens when old vendor objects are directly used. Recommend a concrete default and ask one focused question.
3. Agree the merchant operation inventory and bounded platform features. Make normal invoicing coverage explicit; keep Adaptive separate and lower priority.
4. Present remaining design sections: durable state/routing and payment failures; native REST API and browser integration boundaries; testing/security/runtime/package migration. Carry sensible prior decisions forward instead of asking the user to design implementation details.
5. Write the agreed spec, with concrete acceptance criteria and source references. Default skill location is `docs/superpowers/specs/YYYY-MM-DD-<topic>-design.md`, unless the user chooses another path. This handoff is not the spec.
6. Make supersession explicit for `docs/PRD.md` and `docs/v4-plans/` once the new spec exists. Preserve history and decide archive/pointer treatment; do not let the next planning session mistake the 52 old plan files for approved implementation instructions. Do not close/update external GitHub issues merely to retire local documents without authorization for that action.
7. Self-review the spec for contradictions, placeholders, ambiguous promises, and scope. Present it for user review. **Stop before implementation planning.**

The design should describe behavior, boundaries, and acceptance evidence. Avoid recreating a 52-task implementation plan inside the spec.
