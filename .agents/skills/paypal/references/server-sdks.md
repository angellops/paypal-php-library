# Server SDKs

PayPal publishes official Server SDKs in six languages. They wrap auth and provide typed bindings for **Orders v2, Payments v2, Vault v3, Transaction Search v1, and Subscriptions v1** — that's all. **Disputes, Invoicing, Payouts, Identity, Webhooks management, and Multi-Party Partner Referrals require direct REST regardless of language.**

No official Go SDK. Go (and other languages) → use REST directly.

Aggregator: https://github.com/paypal/PayPal-Server-SDKs

| Language | Docs | GitHub | Latest verified |
|---|---|---|---|
| PHP | https://developer.paypal.com/serversdk/php/getting-started/how-to-get-started | https://github.com/paypal/PayPal-PHP-Server-SDK | 2.2.0 |
| TypeScript / Node | https://developer.paypal.com/serversdk/js/getting-started/how-to-get-started | https://github.com/paypal/PayPal-TypeScript-Server-SDK | 2.3.0 |
| Python | https://developer.paypal.com/serversdk/python/getting-started/how-to-get-started | https://github.com/paypal/PayPal-Python-Server-SDK | 2.2.0 |
| Java | https://developer.paypal.com/serversdk/java/getting-started/how-to-get-started | https://github.com/paypal/PayPal-Java-Server-SDK | 2.2.0 |
| .NET (C#) | https://developer.paypal.com/serversdk/dotnet/getting-started/how-to-get-started | https://github.com/paypal/PayPal-Dotnet-Server-SDK | 2.2.0 |
| Ruby | https://developer.paypal.com/serversdk/ruby/getting-started/how-to-get-started | https://github.com/paypal/PayPal-Ruby-Server-SDK | 2.2.0 |

Always pin the latest version from the README — these change.

## Common patterns

All six SDKs are codegen'd from the same OpenAPI spec, so they share structure:

- One `Client` builder with `clientCredentialsAuthCredentials` (id + secret) + `Environment` enum (`SANDBOX` or `PRODUCTION`).
- Six controllers: Orders, Payments, Vault, TransactionSearch, Subscriptions, OAuthAuthorization.
- Methods take an "input" / "collect" object with named keys including `body` (the request payload) and `prefer` (the header — `'return=minimal'` or `'return=representation'`).
- Token auto-fetched on first call (READMEs don't explicitly document this but the examples show no manual token call).

Splits:
- **Java and .NET are async-only** (`createOrderAsync`, returns `CompletableFuture` / `Task`).
- **TypeScript** is async (Promise-based).
- **PHP, Python, Ruby** are synchronous.
- Error handling splits two ways: exceptions (TS, .NET, Java throw `ApiException`/`ApiError`) or result objects (Python `result.is_success()`, Ruby `result.success?`).

Below: minimal create-order + capture-order per language. Replace `'currency_code6'` and `'value0'` with real values.

## TypeScript / Node

Install: `npm install @paypal/paypal-server-sdk@2.3.0`

```typescript
import {
  Client,
  Environment,
  LogLevel,
  CheckoutPaymentIntent,
  ApiError,
} from '@paypal/paypal-server-sdk';

const client = new Client({
  clientCredentialsAuthCredentials: {
    oAuthClientId:     process.env.PAYPAL_CLIENT_ID!,
    oAuthClientSecret: process.env.PAYPAL_CLIENT_SECRET!,
  },
  timeout: 0,
  environment: Environment.Sandbox,   // or Environment.Production
  logging: {
    logLevel: LogLevel.Info,
    logRequest:  { logBody: true },
    logResponse: { logHeaders: true },
  },
});

const ordersController = client.ordersController;

// Create
try {
  const response = await ordersController.createOrder({
    body: {
      intent: CheckoutPaymentIntent.Capture,
      purchaseUnits: [{ amount: { currencyCode: 'USD', value: '100.00' } }],
    },
    prefer: 'return=minimal',
  });
  const orderId = response.result.id;
} catch (e) {
  if (e instanceof ApiError) console.error(e.statusCode, e.body);
  throw e;
}

// Capture
const cap = await ordersController.captureOrder({
  id: orderId,
  prefer: 'return=representation',
});
```

Error handling shape: `ApiError` has `statusCode`, `headers`, `body`. Some calls also throw a `CustomError` subtype with `result`.

## Python

Install: `pip install paypal-server-sdk==2.2.0`

```python
import os
from paypalserversdk.configuration import Environment
from paypalserversdk.http.auth.o_auth_2 import ClientCredentialsAuthCredentials
from paypalserversdk.paypal_serversdk_client import PaypalServersdkClient
from paypalserversdk.models.checkout_payment_intent import CheckoutPaymentIntent
from paypalserversdk.models.order_request import OrderRequest
from paypalserversdk.models.purchase_unit_request import PurchaseUnitRequest
from paypalserversdk.models.amount_with_breakdown import AmountWithBreakdown

client = PaypalServersdkClient(
    client_credentials_auth_credentials=ClientCredentialsAuthCredentials(
        o_auth_client_id=os.environ["PAYPAL_CLIENT_ID"],
        o_auth_client_secret=os.environ["PAYPAL_CLIENT_SECRET"],
    ),
    environment=Environment.SANDBOX,    # or Environment.PRODUCTION
)
orders = client.orders

# Create
result = orders.create_order({
    "body": OrderRequest(
        intent=CheckoutPaymentIntent.CAPTURE,
        purchase_units=[
            PurchaseUnitRequest(amount=AmountWithBreakdown(
                currency_code="USD", value="100.00"
            ))
        ],
    ),
    "prefer": "return=minimal",
})
if result.is_success():
    order_id = result.body.id
else:
    print(result.errors)

# Capture
cap = orders.capture_order({"id": order_id, "prefer": "return=representation"})
```

## PHP

Install: `composer require "paypal/paypal-server-sdk:2.2.0"`

```php
<?php
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;

$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials(
        ClientCredentialsAuthCredentialsBuilder::init(
            getenv('PAYPAL_CLIENT_ID'),
            getenv('PAYPAL_CLIENT_SECRET')
        )
    )
    ->environment(Environment::SANDBOX)   // or Environment::PRODUCTION
    ->build();

$orders = $client->getOrdersController();

// Create
$response = $orders->createOrder([
    'body' => OrderRequestBuilder::init(
        CheckoutPaymentIntent::CAPTURE,
        [ PurchaseUnitRequestBuilder::init(
              AmountWithBreakdownBuilder::init('USD', '100.00')->build()
          )->build() ]
    )->build(),
    'prefer' => 'return=minimal',
]);
$orderId = $response->getResult()->getId();

// Capture
$cap = $orders->captureOrder(['id' => $orderId, 'prefer' => 'return=representation']);
```

## Java

Install (Maven):
```xml
<dependency>
  <groupId>com.paypal.sdk</groupId>
  <artifactId>paypal-server-sdk</artifactId>
  <version>2.2.0</version>
</dependency>
```

Java SDK is async-only:

```java
import com.paypal.sdk.Environment;
import com.paypal.sdk.PaypalServerSdkClient;
import com.paypal.sdk.authentication.ClientCredentialsAuthModel;
import com.paypal.sdk.controllers.OrdersController;
import com.paypal.sdk.models.*;

PaypalServerSdkClient client = new PaypalServerSdkClient.Builder()
    .clientCredentialsAuth(new ClientCredentialsAuthModel.Builder(
        System.getenv("PAYPAL_CLIENT_ID"),
        System.getenv("PAYPAL_CLIENT_SECRET")
    ).build())
    .environment(Environment.SANDBOX)   // or Environment.PRODUCTION
    .build();

OrdersController orders = client.getOrdersController();

CreateOrderInput input = new CreateOrderInput.Builder(
    null,   // first arg is PayPal-Request-Id (idempotency); pass a UUID in production
    new OrderRequest.Builder(
        CheckoutPaymentIntent.CAPTURE,
        Arrays.asList(new PurchaseUnitRequest.Builder(
            new AmountWithBreakdown.Builder("USD", "100.00").build()
        ).build())
    ).build()
).prefer("return=minimal").build();

orders.createOrderAsync(input)
    .thenAccept(r -> System.out.println(r.getResult().getId()))
    .exceptionally(e -> { e.printStackTrace(); return null; });
```

## .NET (C#)

Install: `dotnet add package PayPalServerSDK --version 2.2.0`

```csharp
using PaypalServerSdk.Standard;
using PaypalServerSdk.Standard.Authentication;
using PaypalServerSdk.Standard.Controllers;
using PaypalServerSdk.Standard.Exceptions;
using PaypalServerSdk.Standard.Models;

var client = new PaypalServerSdkClient.Builder()
    .ClientCredentialsAuth(new ClientCredentialsAuthModel.Builder(
        Environment.GetEnvironmentVariable("PAYPAL_CLIENT_ID"),
        Environment.GetEnvironmentVariable("PAYPAL_CLIENT_SECRET")
    ).Build())
    .Environment(PaypalServerSdk.Standard.Environment.Sandbox)   // or .Production
    .Build();

OrdersController orders = client.OrdersController;

var input = new CreateOrderInput {
    Body = new OrderRequest {
        Intent = CheckoutPaymentIntent.Capture,
        PurchaseUnits = new List<PurchaseUnitRequest> {
            new PurchaseUnitRequest {
                Amount = new AmountWithBreakdown {
                    CurrencyCode = "USD",
                    MValue = "100.00",   // note: MValue, not Value (C# `value` is a contextual keyword)
                },
            },
        },
    },
    Prefer = "return=minimal",
};
try {
    ApiResponse<Order> result = await orders.CreateOrderAsync(input);
    string orderId = result.Data.Id;
} catch (ApiException e) {
    Console.WriteLine($"{e.ResponseCode}: {e.Message}");
}
```

## Ruby

Install: `gem 'paypal-server-sdk', '2.2.0'`

```ruby
require 'paypal_server_sdk'
include PaypalServerSdk

client = Client.new(
  client_credentials_auth_credentials: ClientCredentialsAuthCredentials.new(
    o_auth_client_id:     ENV['PAYPAL_CLIENT_ID'],
    o_auth_client_secret: ENV['PAYPAL_CLIENT_SECRET']
  ),
  environment: Environment::SANDBOX    # or Environment::PRODUCTION
)
orders = client.orders

# Create
result = orders.create_order({
  'body' => OrderRequest.new(
    intent: CheckoutPaymentIntent::CAPTURE,
    purchase_units: [ PurchaseUnitRequest.new(
      amount: AmountWithBreakdown.new(currency_code: 'USD', value: '100.00')
    )]
  ),
  'prefer' => 'return=minimal'
})
if result.success?
  order_id = result.data.id
else
  warn result.errors
end

# Capture
cap = orders.capture_order({ 'id' => order_id, 'prefer' => 'return=representation' })
```

`Client.from_env` is a Ruby-only convenience constructor that reads credentials from env vars.

## Picking SDK vs direct REST

**Use the SDK when:**
- Doing common Orders + Payments flows.
- You like type safety / IDE autocomplete.
- You want the SDK's logging plumbing.

**Drop to REST when:**
- You need an API not in the SDK (Disputes, Invoicing, Payouts, Identity, Multi-Party, Webhooks management).
- You're in Go, Rust, Elixir, or another unsupported language.
- You need fine control over headers (`PayPal-Auth-Assertion`, `PayPal-Mock-Response`, custom `PayPal-Request-Id`).

It's normal to use both: SDK for orders, raw REST for invoicing/payouts/etc.
