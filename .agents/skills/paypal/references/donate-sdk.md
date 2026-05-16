# Donate SDK

A drop-in JS button for nonprofits / charities to accept one-time donations through PayPal Giving Fund. Uses a different SDK from the standard checkout JS SDK.

Docs: https://developer.paypal.com/sdk/donate/

## Setup

You need a **hosted button ID** generated via PayPal:
1. Sign into your PayPal business account at paypal.com.
2. Navigate to PayPal Donations setup (search "Donate Button" in the merchant tools).
3. Generate a Donate button. PayPal returns a `hosted_button_id`.

In sandbox, generate the button under a sandbox business account; the resulting `hosted_button_id` is sandbox-only.

## Embed

```html
<!DOCTYPE html>
<html>
<body>
  <div id="paypal-donate-button-container"></div>

  <script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>
  <script>
    PayPal.Donation.Button({
      env: 'sandbox',                                        // 'sandbox' or 'production'
      hosted_button_id: 'YOUR_SANDBOX_HOSTED_BUTTON_ID',
      // business: 'merchant-email-or-payerid@example.com',  // alternative to hosted_button_id
      image: {
        src:   'https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif',
        title: 'PayPal - The safer, easier way to pay online!',
        alt:   'Donate with PayPal button'
      },
      onComplete: function (params) {
        // params: { tx, st, amt, cc, cm, item_number, item_name }
        //   tx = transaction ID
        //   st = transaction status
        //   amt = amount
        //   cc = currency
        console.log('Donation completed:', params);
      }
    }).render('#paypal-donate-button-container');
  </script>
</body>
</html>
```

## Configuration options

- `env` — `'sandbox'` or `'production'`
- `hosted_button_id` — required for business accounts; ID generated above
- `business` — alternative to `hosted_button_id`; use the business email or PayerID
- `image` — `{ src, title, alt }` for a custom button image. PayPal hosts standard images at `https://www.paypalobjects.com/en_US/i/btn/`
- `onComplete(params)` — callback after donation

## Server-side: tracking donations

PayPal sends an IPN (legacy) or webhook for donation transactions. For modern integrations, subscribe to:
- `PAYMENT.SALE.COMPLETED` — donation captured

Webhook handling: see `webhooks.md`.

For receipts, PayPal automatically emails a receipt to the donor. If you want to track donations in your CRM or send a personalized thank-you, use the webhook payload — `resource.id` is the transaction ID, `resource.amount.total` is the amount, `resource.payer.email` is the donor (if they consented to share).

## Donate vs Standard Checkout

Use Donate SDK when:
- The merchant is a registered nonprofit (PayPal Giving Fund eligibility).
- You want PayPal-managed receipts and tax receipts.
- You don't need control over the page layout — just embed a button.

Use Standard Checkout (`checkout-standard.md`) when:
- You want full control over the donation flow / page.
- The donor needs to choose between donation tiers, recurring donations, dedications, etc.
- You're not a charity but processing a one-off "support us" payment.

For **recurring donations**, use Subscriptions (`subscriptions.md`) with a Plan priced as the monthly donation amount.

## Reference URLs

- Donate SDK: https://developer.paypal.com/sdk/donate/
- PayPal Giving Fund: https://www.paypal.com/us/fundraiser/charity (consumer-facing)
- Webhook event names: https://developer.paypal.com/api/rest/webhooks/event-names/
