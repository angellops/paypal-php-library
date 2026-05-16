---
name: paypal
description: Comprehensive guide to integrating PayPal payment services into any application. Use this skill whenever the user mentions PayPal, accepting payments via PayPal, Pay with PayPal, PayPal Checkout, PayPal Subscriptions, recurring billing with PayPal, PayPal Invoicing, PayPal Payouts (sending money out), PayPal disputes/chargebacks, PayPal webhooks, PayPal sandbox testing, the PayPal REST API, PayPal SDK (JS, PHP, Node, Python, Java, .NET, Ruby), PayPal mobile SDKs (iOS, Android), Log in with PayPal / PayPal SSO, building a marketplace or platform with PayPal, the PayPal Agent Toolkit, the PayPal MCP server, PayPal Donate buttons, or any task that involves implementing or debugging a PayPal integration in any language or framework. Trigger even when PayPal is one option among multiple payment processors being evaluated, or when the user only references "checkout" / "subscriptions" / "payouts" / "invoicing" in a context that suggests PayPal.
---

# PayPal integration

You are helping a developer integrate PayPal. PayPal's API surface is large and changes — this skill is the routing layer plus the universal foundations every PayPal integration needs. Per-domain detail lives in `references/`.

## Cardinal rules

1. **Never invent endpoints, parameter names, lifecycle states, event names, or SDK methods.** PayPal's docs are the source of truth. Every fact in this skill came from a docs page or official GitHub repo, with caveats flagged where the docs were ambiguous. If the user asks for something specific that this skill doesn't cover, fetch the relevant PayPal docs page (URLs throughout) before answering.

2. **Sandbox and Live are completely separate accounts.** Different client IDs, different secrets, different account directories. Always confirm which environment the user is on. Default to sandbox for any new code; the user must opt in to live with their production credentials.

3. **The client secret never leaves the server.** Browser SDK calls use only the `client-id` (public). Token exchange and Orders/Payments/Subscriptions/Payouts/Webhooks management API calls happen server-side with the secret.

4. **PayPal returns `debug_id` on every error response.** Surface it in the user's logs — PayPal Support uses it to trace requests.

5. **When a user is debugging, the answer is usually in the actual API response.** Have them paste the response body (with secrets redacted) before guessing. Read fields like `seller_response_due_date`, `status`, `outcome_code` from the response — don't compute or assume them.

## Routing — which reference to read

Pick the file matching the user's task. You can read multiple if the task spans domains (e.g., "subscriptions with webhooks" needs `subscriptions.md` + `webhooks.md`).

| User wants to... | Read |
|---|---|
| Add a Pay-with-PayPal button to a website | `references/checkout-standard.md` |
| Accept credit cards directly on their site (no PayPal redirect) | `references/checkout-advanced.md` |
| Use a PayPal Server SDK (PHP, Node/TS, Python, Java, .NET, Ruby) | `references/server-sdks.md` |
| Add PayPal to an iOS or Android app | `references/mobile-sdks.md` |
| Build recurring billing / subscriptions / SaaS pricing | `references/subscriptions.md` |
| Send invoices to customers and get paid | `references/invoicing.md` |
| Build a marketplace where multiple sellers accept payments | `references/multi-party.md` |
| Send money OUT to many recipients (mass payouts) | `references/payouts.md` |
| Handle chargebacks, disputes, evidence submission | `references/disputes.md` |
| Pull transaction history, settlement reports, balances | `references/reports.md` |
| Add "Log in with PayPal" SSO to an app | `references/identity.md` |
| Add a Donate button (nonprofit) | `references/donate-sdk.md` |
| Receive event notifications (webhooks) and verify signatures | `references/webhooks.md` |
| Test in sandbox / simulate failures (negative testing) | `references/sandbox-testing.md` |
| Connect an AI agent (LangChain, OpenAI Agents, Claude Desktop, Cursor) to PayPal | `references/ai-tools.md` |

If the user's intent is unclear, ask one targeted question rather than guessing — e.g., "Are you accepting payments from customers, or sending payouts to recipients?" makes the routing trivial.

## Universal foundations

These apply across every reference. Read this section once and don't re-cover it in per-domain answers unless directly asked.

### Base URLs

| Environment | API base | Web (checkout redirect) |
|---|---|---|
| Sandbox | `https://api-m.sandbox.paypal.com` | `https://www.sandbox.paypal.com` |
| Live (Production) | `https://api-m.paypal.com` | `https://www.paypal.com` |

The `-m` is required. The older `api.paypal.com` / `api.sandbox.paypal.com` hosts have been replaced.

### OAuth 2.0 access token

Every server-side REST call needs a Bearer token. Get one with HTTP Basic auth using your client_id and secret:

```bash
curl -X POST "https://api-m.sandbox.paypal.com/v1/oauth2/token" \
  -u "CLIENT_ID:CLIENT_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials"
```

Response shape (verbatim from docs):
```json
{
  "scope": "...",
  "access_token": "<long opaque string>",
  "token_type": "Bearer",
  "app_id": "APP-...",
  "expires_in": 31668,
  "nonce": "..."
}
```

`expires_in` is seconds — PayPal's sample shows ~8h 48m. Cache the token in-process and refresh on `expires_in - 60s` or on the first 401. Never bake the lifetime into code; read it from the response.

Use the token on subsequent calls:
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

Source: https://developer.paypal.com/api/rest/authentication/

### Standard request headers

| Header | Purpose | When required |
|---|---|---|
| `Authorization: Bearer <token>` | Access token | Always |
| `Content-Type: application/json` | JSON body | Any POST/PATCH/PUT with a body |
| `Accept: application/json` | Expect JSON response | Calls with a response body |
| `PayPal-Request-Id: <uuid>` | Idempotency key (stored 45 days) | **Mandatory for single-step Create Order**; recommended for any retryable POST |
| `Prefer: return=representation` | Return full resource (vs minimal) | When you need the full response from capture/authorize |
| `PayPal-Partner-Attribution-Id: <BN-CODE>` | Revenue attribution / partner tracking | Required for multi-party calls |
| `PayPal-Auth-Assertion: <JWT>` | Act on a seller's behalf | Multi-party only |

Source: https://developer.paypal.com/api/rest/requests/

### Error response shape

Standard errors (Orders, Payments, Subscriptions, etc.):
```json
{
  "name": "ERROR_NAME",
  "message": "Error message.",
  "debug_id": "f05063556e6c1",
  "details": [
    { "field": "purchase_units[0].amount.value",
      "value": "abc",
      "issue": "DECIMAL_PRECISION",
      "description": "Decimal precision for the field is wrong." }
  ],
  "links": [{ "href": "...", "rel": "...", "method": "..." }]
}
```

Identity/OAuth errors use a different shape:
```json
{ "error": "invalid_client", "error_description": "Client Authentication failed" }
```

**Always log `debug_id`.** That's what PayPal Support needs.

HTTP status semantics:
- **400** — malformed JSON / schema violation (the request shape is wrong)
- **401** — bad/expired token, or partner not approved for an API
- **403** — token valid but lacks permission
- **404** — resource (order, subscription, dispute) not found
- **409** — `PREVIOUS_REQUEST_IN_PROGRESS` (idempotency conflict)
- **422** — `UNPROCESSABLE_ENTITY` (business rule violation: order not approvable, currency mismatch, etc.). Treat differently from 400 — your JSON was fine, the operation was rejected.
- **2xx on capture**: `201 Created` is a fresh action, `200 OK` is an idempotent replay (you got an existing capture back). Don't conflate.

Source: https://developer.paypal.com/api/rest/responses/

### Versioning

Path-based: `/v1/...` for older APIs (OAuth, Webhooks, Subscriptions, Payouts, Disputes, Identity) and `/v2/...` for the modern Orders, Payments, Invoicing, and Partner-Referrals APIs. No header-based versioning. New checkout integrations should use Orders v2 (the older `/v1/payments/payment` is deprecated).

### Money formatting

- All amounts are **strings**, not numbers: `"value": "100.00"`.
- 31 supported currencies. The full list and per-currency rules: https://developer.paypal.com/api/rest/reference/currency-codes/
- **HUF, JPY, TWD reject decimals.** Send whole-integer strings: `"100"` not `"100.00"`.
- BRL, CNY, MYR are restricted to in-country accounts.

### Credentials and the developer dashboard

- Apps + credentials live at https://developer.paypal.com/dashboard/applications/sandbox (sandbox) and `/applications/live` (live).
- Sandbox accounts (test buyers and sellers) live at https://developer.paypal.com/dashboard/accounts. Two are auto-created at signup; create more as needed.
- Webhook simulator: https://developer.paypal.com/dashboard/webhooksSimulator/
- Events dashboard (delivered/failed webhooks): https://dashboard.paypal.com/webhooks/sandbox or `/live`.

## Picking the right integration shape

Two questions decide most of the architecture.

**Question 1 — Where does the user enter payment info?**
- On a PayPal-hosted page (popup/redirect) → **Standard Checkout** (`checkout-standard.md`). Easiest setup, no PCI scope.
- On the merchant's own site / app, with card fields rendered inline → **Advanced (Expanded) Checkout** (`checkout-advanced.md`). Requires extra onboarding ("Request Expanded Credit and Debit Card Payments" in dashboard) and gives PCI SAQ A scope.

**Question 2 — Direct API or use a Server SDK?**
- Direct REST is fine for any language. Use it for languages PayPal doesn't ship an SDK for (Go, Rust, Elixir, etc.) or when you only need a couple of endpoints.
- Server SDKs (`server-sdks.md`) cover PHP, Node/TS, Python, Java, .NET, Ruby. They wrap auth and give typed responses but **only cover Orders v2, Payments v2, Vault v3, Transaction Search v1, and Subscriptions v1**. Disputes, Invoicing, Payouts, Identity, and Webhooks management require direct REST regardless of language.

## When the docs are unclear or this skill is silent

Fetch the canonical doc page. Top-level entry points:
- API reference: https://developer.paypal.com/api/rest/
- All product docs: https://developer.paypal.com/docs/
- Webhook event names: https://developer.paypal.com/api/rest/webhooks/event-names/
- AI tools (Agent Toolkit + MCP): https://docs.paypal.ai/

Quote what the docs say. Tell the user when something isn't in the docs vs when you're paraphrasing. PayPal's docs occasionally have rendering bugs (a duplicated slash in one curl example, inconsistent invoice-number endpoint paths) — call them out rather than copy them.

## Common gotchas worth surfacing proactively

- **Idempotency** — without `PayPal-Request-Id`, a network retry on Create Order can create duplicate orders. Always send a UUID.
- **`Prefer: return=minimal` is the default.** If the user expects a full capture/authorize response, they need `Prefer: return=representation`.
- **`application_context` is deprecated** at the top level of Create Order. Move `brand_name`, `return_url`, `cancel_url`, `landing_page`, `user_action`, `shipping_preference` under `payment_source.<method>.experience_context` instead.
- **HATEOAS links are authoritative.** The `payer-action` link in a Create Order response is what to redirect the buyer to (or what the JS SDK uses internally). Don't construct the approval URL by hand.
- **Sandbox business accounts have their own client_id/secret.** A new sandbox business account doesn't share credentials with the developer's main account — generate or look up the app under that specific account.
- **British vs. American spelling in enums.** PayPal returns `RESOLVED_BUYER_FAVOUR` and `UNAUTHORISED` in some dispute fields (with the British 'OUR'/'ISED'). Match casing exactly when filtering.
- **Authorization honor period is 3 days**, extendable to 29 with `/reauthorize`. After that the auth is void and the buyer must re-approve.
