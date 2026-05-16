# Sandbox & negative testing

Sandbox is PayPal's full-featured test environment. Different base URL, different account directory, different credentials — completely isolated from live. Use it for development, staging, automated tests, and reproducing production bugs.

Docs: https://developer.paypal.com/tools/sandbox/

## Base URLs

| Environment | API | Web |
|---|---|---|
| Sandbox | `https://api-m.sandbox.paypal.com` | `https://www.sandbox.paypal.com` |
| Live | `https://api-m.paypal.com` | `https://www.paypal.com` |

## Sandbox accounts

Two account types:
- **Personal** — simulates a buyer (has a PayPal balance, linked card, linked bank).
- **Business** — simulates a merchant; gets test API credentials (client_id/secret) for an associated app.

PayPal auto-creates one Personal (`sb-...@personal.example.com`) and one Business (`sb-...@business.example.com`) at signup. These two are not deletable.

Create more: Developer Dashboard → Sandbox → Accounts → Create Account → choose Personal or Business → choose country. Use "Create Custom Account" to set starting balance, country, password, etc.

URL: https://developer.paypal.com/dashboard/accounts

### Sandbox API credentials

Each Business sandbox account is associated with one or more apps under "Apps & Credentials" → Sandbox. Each app has its own client_id/secret pair. Token endpoint:

```bash
curl -X POST "https://api-m.sandbox.paypal.com/v1/oauth2/token" \
  -u "SANDBOX_CLIENT_ID:SANDBOX_CLIENT_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials"
```

### Logging in as a sandbox buyer

When checkout redirects a buyer to PayPal in sandbox, they sign in at `https://www.sandbox.paypal.com/` using the email and (auto-generated) password of a Personal sandbox account. Both are visible / editable on the Sandbox Accounts page; click an account → "View/Edit Account".

## Test cards

PayPal lists static test cards. Use a future expiration date and any 3-digit CVV (4 for Amex):

- **Visa** (used as the carrier for negative-test triggers): `4012 8888 8888 1881`
- **Amex**: `3714 4963 5398 431`, `3766 8081 6376 961`
- **Diners Club**: `3646 1510 0003 9`, `3646 1510 0001 3`
- **Maestro**: `6304 0000 0000 0000`

Generate more in the dashboard: Sandbox → Card Generator. PayPal does **not** publish a list of test bank account numbers — bank-rail tests use the synthetic balances on sandbox accounts.

Card-testing docs: https://developer.paypal.com/tools/sandbox/card-testing/

## Negative testing — simulating errors

PayPal lets you force specific error responses in sandbox so you can verify your error handling. Sandbox-only feature.

Enable per Business account: Sandbox → Accounts → View/Edit Account → Settings → toggle Negative Testing **On**.

### Approach 1 — `PayPal-Mock-Response` request header

Works for Payments v1/v2 and Orders v2:

```
PayPal-Mock-Response: {"mock_application_codes": "INSTRUMENT_DECLINED"}
```

Mock codes verified for Orders v2:

| HTTP | Mock code |
|---|---|
| 400 | `INVALID_PARAMETER_SYNTAX`, `INVALID_STRING_LENGTH`, `MISSING_REQUIRED_PARAMETER`, `MALFORMED_REQUEST_JSON` |
| 401 | `INVALID_ACCOUNT_STATUS` |
| 403 | `PERMISSION_DENIED` |
| 404 | `INVALID_RESOURCE_ID` |
| 409 | `PREVIOUS_REQUEST_IN_PROGRESS` |
| 422 | `INSTRUMENT_DECLINED`, `DUPLICATE_INVOICE_ID`, `CARD_EXPIRED`, `PAYER_ACTION_REQUIRED`, `PAYMENT_DENIED`, `ORDER_NOT_APPROVED`, `CONTINGENCY_NOT_SUCCESSFUL`, `AMOUNT_MISMATCH`, `INVALID_CURRENCY_CODE` |
| 500 | `INTERNAL_SERVER_ERROR` |

Per-API code lists: https://developer.paypal.com/tools/sandbox/negative-testing/request-headers/

### Approach 2 — Magic test values in the request body

Disputes, Invoicing, Payouts, and Subscriptions APIs accept "trigger strings" in specific fields. Example: a Payouts request with `items[0].note = "ERRPYO002"` triggers `SENDER_EMAIL_UNCONFIRMED`.

Per-API trigger tables: https://developer.paypal.com/tools/sandbox/negative-testing/test-values/

### Approach 3 — Card-field name negative triggers

Enter one of these strings as the cardholder name with the Visa test card `4012 8888 8888 1881`:

| Trigger string | ProcessorResponseCode | Outcome |
|---|---|---|
| `CCREJECT-REFUSED` | 0500 | Card refused |
| `CCREJECT-SF` | 9500 | Suspected fraud |
| `CCREJECT-EC` | 5400 | Expired card |
| `CCREJECT-IRC` | 5180 | Invalid/restricted card |
| `CCREJECT-IF` | 5120 | Insufficient funds |
| `CCREJECT-LS` | 9520 | Lost or stolen |
| `CCREJECT-IA` | 1330 | Invalid account |
| `CCREJECT-BANK_ERROR` | 5100 | Generic decline |
| `CCREJECT-CVV_F` | 00N7 | CVV verification failure |

Case-sensitive. Production traffic ignores these strings.

## Sandbox-only constraints

These features are **not available** in sandbox:
- Closing an account
- Issuing monthly statements
- Storing shipping preferences
- PayPal Shops support

These can produce sandbox behavior that differs from live; if a test passes in sandbox but fails in live, suspect one of these or a feature that requires live-account vetting (Expanded Checkout, Multi-Party, Log in with PayPal review).

## Going live

1. In the dashboard, switch the app toggle from Sandbox to Live (top-left of Apps & Credentials).
2. Each app has separate Live credentials — generate or copy them.
3. Live API base URL: `https://api-m.paypal.com`. Live web: `https://www.paypal.com`.
4. Some products (Expanded Checkout, Multi-Party, Log in with PayPal customer-data sharing) require a PayPal review before they work in Live. Plan for **24-72 hours** for app review; up to 7 business days for Log in with PayPal customer-data approval.
5. Re-create your Live webhooks. Sandbox webhook subscriptions don't transfer.

## Useful sandbox URLs

- Dashboard accounts: https://developer.paypal.com/dashboard/accounts
- Apps & credentials (sandbox): https://developer.paypal.com/dashboard/applications/sandbox
- Webhook simulator: https://developer.paypal.com/dashboard/webhooksSimulator/
- Sandbox Events dashboard: https://dashboard.paypal.com/webhooks/sandbox
- Sandbox login (as buyer/seller): https://www.sandbox.paypal.com/
- Sandbox card testing: https://developer.paypal.com/tools/sandbox/card-testing/
- Negative testing: https://developer.paypal.com/tools/sandbox/negative-testing/
