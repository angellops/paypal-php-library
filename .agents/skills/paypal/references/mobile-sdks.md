# Mobile SDKs (iOS / Android)

Native PayPal integration for iPhone, iPad, and Android. Both SDKs cover **PayPal web checkout** (redirect to a PayPal-hosted in-app browser) and **direct card payments** (PCI-scoped card form rendered natively).

The server side is unchanged — your backend still uses Orders v2 to create the order and capture it. The mobile SDK only handles the buyer-facing approval / card-input.

## iOS SDK

Repo: https://github.com/paypal/paypal-ios
Docs: https://developer.paypal.com/docs/checkout/advanced/ios/

### Requirements

- iOS 14+ deployment target
- Swift 5.9+
- Xcode 15.0+

### Install

**Swift Package Manager** (recommended): in Xcode, File → Add Packages → enter `https://github.com/paypal/paypal-ios/` → check the modules you need (`CardPayments`, `PayPalWebPayments`, etc.).

**CocoaPods**:
```ruby
pod 'PayPal/CardPayments'
pod 'PayPal/PayPalWebPayments'
```

Carthage is not supported by PayPal docs — don't expect it.

### Modules

Confirmed: `CardPayments`, `PayPalWebPayments`. The repo also exposes additional modules (`FraudProtection`, `PaymentButtons` referenced in source) — check the GitHub README for the current list.

### Card payments

```swift
import CardPayments

// 1) Server creates the order, returns ORDER_ID
// 2) On the device:
let coreConfig = CoreConfig(clientID: "YOUR_CLIENT_ID", environment: .sandbox)  // or .live
let cardClient = CardClient(config: coreConfig)
cardClient.delegate = self  // implement CardDelegate

let card = Card(
    number: "4111111111111111",
    expirationMonth: "12",
    expirationYear: "2030",
    securityCode: "123"
)
let cardRequest = CardRequest(orderID: "ORDER_ID", card: card)
cardClient.approveOrder(request: cardRequest)
```

`CardDelegate` callbacks:
```swift
func card(_ cardClient: CardClient, didFinishWithResult result: CardResult) {
    // result.orderID, result.deepLinkURL, result.didAttemptThreeDSecureAuthentication
    // Server now captures the order
}
func card(_ cardClient: CardClient, didFinishWithError error: CoreSDKError) { ... }
func cardThreeDSecureWillLaunch(_ cardClient: CardClient) { ... }   // 3DS challenge presented
func cardThreeDSecureDidFinish(_ cardClient: CardClient) { ... }
func cardDidCancel(_ cardClient: CardClient) { ... }
```

After `didFinishWithResult`, your server captures the order via REST.

### PayPal web checkout

For the "Pay with PayPal" wallet flow:

```swift
import PayPalWebPayments

let coreConfig = CoreConfig(clientID: "YOUR_CLIENT_ID", environment: .sandbox)
let webCheckoutClient = PayPalWebCheckoutClient(config: coreConfig)
webCheckoutClient.delegate = self  // PayPalWebCheckoutDelegate

let request = PayPalWebCheckoutRequest(orderID: "ORDER_ID")
webCheckoutClient.start(request: request)
```

The SDK opens a PayPal-hosted page in an in-app browser; on approval/cancel the delegate is called.

### Return-to-app

iOS uses delegate callbacks (above) rather than URL schemes for the card flow. The PayPalWebPayments flow uses an in-app browser (ASWebAuthenticationSession) and returns naturally; no manual URL scheme handling needed in current versions.

## Android SDK

Repo: https://github.com/paypal/paypal-android
Docs: https://developer.paypal.com/docs/checkout/advanced/android/

### Requirements

- minSdk **API 23** (Android 6.0)
- Kotlin (Java-compatible)

### Install (Gradle)

```gradle
dependencies {
  implementation "com.paypal.android:card-payments:<CURRENT_VERSION>"
  implementation "com.paypal.android:paypal-web-payments:<CURRENT_VERSION>"
}
```

Replace `<CURRENT_VERSION>` with the latest from Maven Central. As of the most recent verified release: **2.3.0**.

### Card payments

```kotlin
import com.paypal.android.cardpayments.*
import com.paypal.android.corepayments.CoreConfig
import com.paypal.android.corepayments.Environment

val config = CoreConfig("YOUR_CLIENT_ID", environment = Environment.SANDBOX)  // or LIVE
val cardClient = CardClient(this, config)   // 'this' is your Activity
cardClient.cardDelegate = this              // implement CardDelegate

val card = Card(
    number = "4111111111111111",
    expirationMonth = "12",
    expirationYear = "2030",
    securityCode = "123"
)
val cardRequest = CardRequest(orderId = "ORDER_ID", card = card)
cardClient.approveOrder(this, cardRequest)
```

Delegate callbacks:
```kotlin
override fun onApproveOrderSuccess(result: CardResult) {
    // result.orderId — server captures next
}
override fun onApproveOrderFailure(error: PayPalSDKError) { /* ... */ }
override fun onApproveOrderThreeDSecureWillLaunch() { /* ... */ }
override fun onApproveOrderThreeDSecureDidFinish() { /* ... */ }
override fun onApproveOrderCanceled() { /* ... */ }
```

### PayPal web checkout

```kotlin
import com.paypal.android.paypalwebpayments.*

val config = CoreConfig("YOUR_CLIENT_ID", environment = Environment.SANDBOX)
val returnUrl = "com.example.app://paypalpay"   // your custom URL scheme
val payPalWebClient = PayPalWebCheckoutClient(requireActivity(), config, returnUrl)
payPalWebClient.start(PayPalWebCheckoutRequest("ORDER_ID"))
```

### Activity setup for return-to-app

The web flow requires a custom URL scheme. PayPal docs:

- Set the activity's `launchMode` to `singleTop`.
- Register the custom URL scheme on the Activity that handles the deep link (intent-filter with `android:scheme`).
- Override `onNewIntent`:
  ```kotlin
  override fun onNewIntent(newIntent: Intent?) {
      super.onNewIntent(intent)
      intent = newIntent
  }
  ```

The exact `<intent-filter>` XML wasn't in the docs page I retrieved verbatim — fetch https://developer.paypal.com/docs/checkout/advanced/android/ before publishing for the canonical snippet.

## Mobile architecture pattern

```
Mobile app                 Your backend                PayPal
   │                            │                         │
   │── POST /api/orders ───────▶│                         │
   │                            │── POST /v2/checkout/orders ▶│
   │                            │◀──────── { id } ────────│
   │◀───── { orderID } ─────────│                         │
   │                                                      │
   │── PayPal SDK approveOrder(orderID) ─────────────────▶│
   │◀──── delegate didFinishWithResult ───────────────────│
   │                                                      │
   │── POST /api/orders/:id/capture ───▶│                 │
   │                            │── POST /v2/checkout/orders/{id}/capture ▶│
   │                            │◀────── { COMPLETED } ───│
   │◀────── { success } ────────│                         │
```

Never put the client_secret in the mobile binary. The mobile app only knows the public client_id; everything that needs the secret happens server-side.

## Common pitfalls

- **Sandbox `clientID` won't work in `.live` environment** and vice versa.
- **3DS challenges open a webview** — make sure your activity / view controller stays alive while it's presenting.
- **Custom URL scheme for Android** must be unique to your app or the OS may route the redirect to another app.
- **Server still does the capture.** The mobile SDK gets approval; capture is a server-side REST call.

## Reference URLs

- iOS: https://developer.paypal.com/docs/checkout/advanced/ios/
- iOS GitHub: https://github.com/paypal/paypal-ios
- Android: https://developer.paypal.com/docs/checkout/advanced/android/
- Android GitHub: https://github.com/paypal/paypal-android
- Mobile SDK landing: https://developer.paypal.com/sdk/mobile/
