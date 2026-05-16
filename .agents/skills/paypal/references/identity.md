# Log in with PayPal (Identity / OIDC)

PayPal SSO. Lets users authenticate with their PayPal account on your site, and (with the right scopes) lets you read their name, email, address, and verified-account status. Built on **OAuth 2.0 + OpenID Connect**.

Docs: https://developer.paypal.com/docs/log-in-with-paypal/

**Approval required for production.** PayPal must review your app to approve customer-data sharing — review takes up to 7 business days. Sandbox works without review.

## Endpoints (verified)

| | Sandbox | Live |
|---|---|---|
| Authorization | `https://www.sandbox.paypal.com/signin/authorize` | `https://www.paypal.com/signin/authorize` |
| Token | `https://api-m.sandbox.paypal.com/v1/oauth2/token` | `https://api-m.paypal.com/v1/oauth2/token` |
| Userinfo | `https://api-m.sandbox.paypal.com/v1/identity/oauth2/userinfo` | `https://api-m.paypal.com/v1/identity/oauth2/userinfo` |

The legacy `/connect` authorization endpoint still works but doesn't get new features. For new apps, use `/signin/authorize`.

The Identity API also documents an older userinfo path `GET /v1/identity/openidconnect/userinfo?schema=openid`. Both exist; current docs use `/v1/identity/oauth2/userinfo`.

## Scopes

Verified set:
- `openid` (required) — minimum to authenticate
- `profile` — name
- `email` — email + email_verified
- `address` — postal address
- `phone` — phone number
- `https://uri.paypal.com/services/paypalattributes` — PayPal-specific attributes (account verification status, etc.)

Pass scopes space-separated in the `scope` query param.

## Authorization code flow

### Step 1 — Redirect user to PayPal

Construct an authorization URL:

```
https://www.sandbox.paypal.com/signin/authorize?
  flowEntry=static
  &client_id=YOUR_CLIENT_ID
  &scope=openid+profile+email
  &redirect_uri=https%3A%2F%2Fyour-app.com%2Fauth%2Fpaypal%2Fcallback
  &response_type=code
```

`response_type` is `code` (standard auth-code flow). The `redirect_uri` must be registered against your app in the PayPal Developer Dashboard — PayPal validates the exact match.

Render the official "Log in with PayPal" button image — don't self-host:
```html
<a href="https://www.sandbox.paypal.com/signin/authorize?flowEntry=static&client_id=YOUR_CLIENT_ID&scope=openid+profile+email&redirect_uri=https%3A%2F%2Fyour-app.com%2Fauth%2Fpaypal%2Fcallback&response_type=code">
  <img src="https://www.paypalobjects.com/devdoc/log-in-with-paypal-button.png" alt="Log in with PayPal">
</a>
```

### Step 2 — Handle the callback

PayPal redirects the user back to your `redirect_uri` with `?code=AUTHORIZATION_CODE` (and `state` if you sent one — always send a CSRF state parameter).

### Step 3 — Exchange code for tokens

```bash
curl -X POST https://api-m.sandbox.paypal.com/v1/oauth2/token \
  -u "YOUR_CLIENT_ID:YOUR_CLIENT_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code&code=AUTHORIZATION_CODE&redirect_uri=https%3A%2F%2Fyour-app.com%2Fauth%2Fpaypal%2Fcallback"
```

Response:
```json
{
  "access_token": "A21AAH...",
  "refresh_token": "R23YAA...",
  "token_type": "Bearer",
  "expires_in": 28800,
  "scope": "openid profile email",
  "nonce": "..."
}
```

Default `expires_in` is 28800 seconds (8 hours). Use `refresh_token` to mint new access tokens without re-prompting the user:

```bash
curl -X POST https://api-m.sandbox.paypal.com/v1/oauth2/token \
  -u "YOUR_CLIENT_ID:YOUR_CLIENT_SECRET" \
  -d "grant_type=refresh_token&refresh_token=REFRESH_TOKEN"
```

### Step 4 — Fetch user info

```bash
curl -X GET https://api-m.sandbox.paypal.com/v1/identity/oauth2/userinfo \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

Response includes only the fields covered by your granted scopes. Documented possible fields:

```json
{
  "user_id": "https://www.paypal.com/webapps/auth/identity/user/abc...",
  "sub":     "abc...",
  "name":         "Jane Doe",
  "given_name":   "Jane",
  "family_name":  "Doe",
  "middle_name":  "",
  "picture":      "https://...",
  "email":            "jane@example.com",
  "email_verified":   true,
  "gender":           "female",
  "birthdate":        "1990-05-12",
  "zoneinfo":         "America/Los_Angeles",
  "locale":           "en-US",
  "phone_number":     "+1-415-555-0100",
  "address": {
    "street_address":  "123 Main St",
    "locality":        "San Jose",
    "region":          "CA",
    "postal_code":     "95131",
    "country":         "US"
  },
  "verified_account": true,
  "account_type":     "PERSONAL",
  "age_range":        "20-30"
}
```

`sub` is the unique stable identifier — store it as the user's PayPal ID. `verified_account` indicates whether PayPal has verified the user's identity (useful for KYC-lite workflows).

## Going live

1. In the developer dashboard, configure your app's "Log in with PayPal" settings: `redirect_uri`, requested scopes, privacy policy URL, ToS URL.
2. Submit for review. PayPal evaluates your data-sharing request — up to 7 business days.
3. After approval, switch to live URLs (`www.paypal.com` / `api-m.paypal.com`) and your live client_id/secret.

Sandbox doesn't require this review — useful for development.

## Common pitfalls

- **`redirect_uri` mismatch** — the URI you send in step 1 and step 3 must exactly match what's registered. No trailing slash differences, no protocol mismatches.
- **Mixing live and sandbox endpoints** — sandbox-issued codes don't redeem at live token endpoints.
- **Ignoring `state`** — always send a CSRF token in `state` and verify on callback.
- **Asking for too many scopes** — slows approval, drops user opt-in rate. Ask for `openid profile email` and add others only when needed.
- **Treating `email` as the user's primary key** — emails change. Use `sub`.
- **PayPal review time** — plan for 1+ week before live launch.

## Reference URLs

- Log in with PayPal overview: https://developer.paypal.com/docs/log-in-with-paypal/
- Integration walkthrough: https://developer.paypal.com/docs/log-in-with-paypal/integrate/
- Build the button: https://developer.paypal.com/docs/log-in-with-paypal/integrate/build-button/
- Identity v1 API: https://developer.paypal.com/docs/api/identity/v1/
- OAuth/Authentication ref: https://developer.paypal.com/api/rest/authentication/
