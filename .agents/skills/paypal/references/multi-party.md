# Multi-Party (Marketplaces & Platforms)

For platforms that onboard sellers and process payments on their behalf — Etsy-style marketplaces, SaaS that white-label payments for their customers, booking platforms, etc. The platform takes a commission via `platform_fees`.

**Approval-gated.** "The PayPal Complete Payments Platform is only available to approved partners. Calls to the Multi-Party APIs without approval will return a `401 Unauthorized`." Direct the user to apply via PayPal partner sales before they can build past sandbox.

Docs: https://developer.paypal.com/docs/multiparty/

## Roles

- **Platform** — the marketplace/SaaS building the integration (the API caller).
- **Partner** — PayPal itself.
- **Seller / Merchant** — the businesses the platform onboards.
- **Buyer** — end customer paying through the platform.

## Onboarding methods

PayPal documents three:
- **Before Payment** (recommended). Onboard sellers as part of platform signup. Supports Standard + Expanded Checkout.
- **After Payment**. Sellers get onboarded inline during their first sale. PayPal Checkout only, business sellers only.
- **Build Into Software**. Downloadable plugins.

This reference focuses on the Before Payment API flow.

## High-level flow

```
1. Platform calls POST /v2/customer/partner-referrals
   → returns an action_url
2. Platform redirects seller to that URL
   → seller completes signup + grants permissions on PayPal
3. PayPal redirects seller back to platform's return_url with merchant ID
   (also sends MERCHANT.ONBOARDING.COMPLETED webhook)
4. Platform stores seller's merchantIdInPayPal
5. Platform creates orders on seller's behalf using:
   - payee.merchant_id           (which seller gets paid)
   - PayPal-Auth-Assertion header (which seller you're acting as)
   - PayPal-Partner-Attribution-Id header (your BN code)
   - payment_instruction.platform_fees (your cut)
```

## Step 1 — Generate the partner referral

```
POST /v2/customer/partner-referrals
Authorization: Bearer <PARTNER_ACCESS_TOKEN>
Content-Type: application/json
```

```json
{
  "tracking_id": "your-internal-seller-id-123",
  "operations": [{
    "operation": "API_INTEGRATION",
    "api_integration_preference": {
      "rest_api_integration": {
        "integration_method": "PAYPAL",
        "integration_type": "THIRD_PARTY",
        "third_party_details": {
          "features": ["PAYMENT", "REFUND"]
        }
      }
    }
  }],
  "products": ["EXPRESS_CHECKOUT"],
  "legal_consents": [{ "type": "SHARE_DATA_CONSENT", "granted": true }],
  "partner_config_override": {
    "return_url": "https://platform.example.com/onboarding/complete",
    "return_url_description": "Return to platform after PayPal onboarding",
    "show_add_credit_card": true
  }
}
```

Documented `products` enum values: `PPCP`, `EXPRESS_CHECKOUT`, `PAYMENT_METHODS`, `ALIPAY`, `WECHAT_PAY`, `ZETTLE`. Documented `capabilities` (orthogonal extras): `PAYPAL_WALLET_VAULTING_ADVANCED`, `APPLE_PAY`, `GOOGLE_PAY`.

Optional top-level fields: `individual_owners`, `business_entity`, `email`, `preferred_language_code`, `payout_attributes`, `legal_country_code`. These pre-fill the seller's signup form.

Response `201` includes HATEOAS links; find the one with `rel: "action_url"` (sample: `https://www.paypal.com/merchantsignup/partner/onboardingentry?token=...`). Redirect the seller there.

## Step 2 — Seller completes onboarding

The seller signs up (or signs into existing account), reviews permissions, and grants. PayPal redirects back to your `partner_config_override.return_url` with these query params:

```
https://platform.example.com/onboarding/complete?
  merchantId=your-internal-seller-id-123
  &merchantIdInPayPal=ABC123MERCHANTID
  &permissionsGranted=true
  &accountStatus=BUSINESS_ACCOUNT
  &consentStatus=true
```

- `merchantId` — your `tracking_id` echoed back.
- `merchantIdInPayPal` — **the seller's PayPal merchant ID. Store it.** You'll use it on every future API call as `payee.merchant_id` and inside `PayPal-Auth-Assertion`.
- `permissionsGranted` — boolean. False means the seller declined some permissions; you may need to re-prompt.
- `accountStatus` — typically `BUSINESS_ACCOUNT`.
- `consentStatus` — boolean.

You should **also** subscribe to the webhooks below — relying solely on the redirect is fragile (browser closes, network drops).

## Step 3 — Listen for onboarding webhooks

Subscribe to:
- `MERCHANT.ONBOARDING.COMPLETED` — fires when seller meets all requirements
- `MERCHANT.PARTNER-CONSENT.REVOKED` — fires when seller revokes your platform's permissions (handle gracefully — stop processing for them)
- `CUSTOMER.MERCHANT-INTEGRATION.SELLER-CONSENT-GRANTED` / `SELLER-EMAIL-CONFIRMED` / `CAPABILITY-UPDATED` — granular status updates

See `webhooks.md` for the verification pattern.

## Step 4 — Check seller status before processing payments

```
GET /v1/customer/partners/{partner_merchant_id}/merchant-integrations/{merchant_id}
```

`partner_merchant_id` is the platform's PayPal merchant ID; `merchant_id` is the seller's `merchantIdInPayPal`.

Two fields determine readiness:
- `payments_receivable: true` — PayPal will allow funds to be received.
- `primary_email_confirmed: true` — seller has confirmed their email.

A seller is **ready to transact** only when both are `true`. Block order creation for them until then.

## Acting on a seller's behalf

Three headers + a JWT.

### `PayPal-Partner-Attribution-Id` (BN code)

Your platform's BN code (Build Notation), issued by PayPal at partner approval. Required on every multi-party request:

```
PayPal-Partner-Attribution-Id: ACME_PLATFORM_PSP
```

### `PayPal-Auth-Assertion` (the JWT)

An **unsigned** JSON Web Token telling PayPal which seller you're acting for.

Header (Base64-encoded):
```json
{"alg": "none"}
```

Payload (Base64-encoded):
```json
{
  "iss":      "<platform_client_id>",
  "payer_id": "<seller_merchant_id>"
}
```

Signature: empty string. Concatenate with periods, leave the signature segment empty:
```
<base64(header)>.<base64(payload)>.
```

PayPal recommends `payer_id` over `email` because emails change.

Example construction (Node):
```javascript
function buildAuthAssertion(partnerClientId, sellerMerchantId) {
  const header  = Buffer.from(JSON.stringify({ alg: "none" })).toString("base64");
  const payload = Buffer.from(JSON.stringify({
    iss: partnerClientId,
    payer_id: sellerMerchantId
  })).toString("base64");
  return `${header}.${payload}.`;   // trailing period intentional
}
```

### `PayPal-Request-Id`

Idempotency key — same as for any other Create Order call. Use a UUID; PayPal stores it for 45 days.

## Create an order on a seller's behalf

```bash
curl -X POST https://api-m.sandbox.paypal.com/v2/checkout/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <PARTNER_ACCESS_TOKEN>" \
  -H "PayPal-Partner-Attribution-Id: <BN_CODE>" \
  -H "PayPal-Auth-Assertion: <BASE64_JWT>" \
  -H "PayPal-Request-Id: <UUID>" \
  -d '{
    "intent": "CAPTURE",
    "purchase_units": [{
      "amount": { "currency_code": "USD", "value": "100.00" },
      "payee": { "merchant_id": "<SELLER_MERCHANT_ID>" },
      "payment_instruction": {
        "disbursement_mode": "INSTANT",
        "platform_fees": [{
          "amount": { "currency_code": "USD", "value": "25.00" }
        }]
      }
    }]
  }'
```

`payment_instruction.disbursement_mode`:
- `INSTANT` (default) — funds move from buyer → seller (minus platform_fees, which go to the platform) at capture time.
- `DELAYED` — funds held; platform releases later via the Payments API.

`platform_fees[].amount.currency_code` must match the order currency.

## Refunds on a seller's behalf

Same headers, against the capture ID:

```bash
curl -X POST https://api-m.sandbox.paypal.com/v2/payments/captures/{capture_id}/refund \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <PARTNER_ACCESS_TOKEN>" \
  -H "PayPal-Partner-Attribution-Id: <BN_CODE>" \
  -H "PayPal-Auth-Assertion: <BASE64_JWT>" \
  -H "PayPal-Request-Id: <UUID>" \
  -d '{}'
```

Empty body for full refund; partial as in `checkout-standard.md`. Platform_fees may be reversed proportionally.

## JS SDK on the buyer side

The buyer-facing JS SDK script tag adds two attributes for marketplaces:

```html
<script
  src="https://www.paypal.com/sdk/js?client-id=PARTNER_CLIENT_ID&merchant-id=SELLER_MERCHANT_ID&currency=USD"
  data-partner-attribution-id="<BN_CODE>"
></script>
```

`merchant-id` tells PayPal which seller's funds the order is for; `data-partner-attribution-id` carries the BN code on the client side. The `createOrder` callback still goes to your server (which calls Create Order with Auth-Assertion).

## Common pitfalls

- **401 on partner-referrals** = your platform isn't approved for Multi-Party. Sales/partner team is the unblock; building locally won't help until approval.
- **Forgetting `payments_receivable` check** — sellers who haven't confirmed email or hit a limit will accept orders that fail at capture. Always check status first.
- **Auth-Assertion is unsigned** by default. The "none" alg is intentional per PayPal's docs ("the information passed with the JWT is not sensitive data"). Don't sign it unless you have a specific partner agreement requiring it.
- **The `merchantIdInPayPal` you store is permanent for that seller.** Sellers can revoke consent (you'll get a webhook), but if they re-onboard their merchant ID stays the same.
- **Disputes against marketplace transactions** are managed through the same Disputes API but the platform may need to coordinate with the seller. See `disputes.md`.
- **Don't store the seller's PayPal email as their primary identifier.** Emails change. Use the merchant ID.

## Reference URLs

- Multi-Party overview: https://developer.paypal.com/docs/multiparty/
- Get started: https://developer.paypal.com/docs/multiparty/get-started/
- Seller onboarding (Before Payment): https://developer.paypal.com/docs/multiparty/seller-onboarding/before-payment/
- Seller onboarding (After Payment): https://developer.paypal.com/docs/multiparty/seller-onboarding/after-payment/
- Multi-Party Checkout: https://developer.paypal.com/docs/multiparty/checkout/
- Issue Refunds: https://developer.paypal.com/docs/multiparty/issue-refund/
- Partner Referrals v2 API: https://developer.paypal.com/docs/api/partner-referrals/v2/
- PayPal-Auth-Assertion construction: https://developer.paypal.com/api/rest/requests/#paypal-auth-assertion
