<?php

namespace angelleye\PayPal;

/**
 * PayPal REST API Class
 * Extends the main PayPal class for consistency and shared functionality
 */
class PayPalREST extends PayPal
{
    // REST-specific properties
    private $accessToken;
    private $tokenExpiry;
    private $client_id;
    private $client_secret;
    private $base_url;

    public function __construct($config)
    {
        $this->Sandbox = isset($config['Sandbox']) ? true : false;

        // Override base URL for REST API endpoints
        $this->base_url = $this->Sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        // Set REST-specific credentials
        $this->client_id = isset($config['ClientID']) ? $config['ClientID'] : '';
        $this->client_secret = isset($config['ClientSecret']) ? $config['ClientSecret'] : '';

        // Parent class already handles: sandbox, print_headers, log_results, log_path, base URLs
    }

    /**
     * Test method to verify class instantiation
     */
    public function test()
    {
        return [
            'success' => true,
            'message' => 'PayPalREST class initialized successfully',
            'base_url' => $this->base_url,  // Now matches the property
            'mode' => $this->Sandbox  // Now this exists
        ];
    }

    /**
     * Get standard headers for API requests
     */
    private function getHeaders($includeAuth = true, $contentType = 'application/json')
    {
        $headers = [
        'Content-Type: ' . $contentType,
        'Accept: application/json',
        'Partner-Attribution-Id: AngellEYELLC_Ecom_PHPCatalog'
        ];

        if ($includeAuth) {
            $token = $this->getAccessToken();
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Get OAuth-specific headers (for token requests)
     */
    private function getOAuthHeaders()
    {
        $auth = base64_encode($this->client_id . ':' . $this->client_secret);

        return [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'Partner-Attribution-Id: AngellEYELLC_Ecom_PHPCatalog'
        ];
    }

    /**
     * Get OAuth 2.0 access token
     * Caches token for 9 hours to avoid redundant API calls
     */
    private function getAccessToken()
    {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        $headers = $this->getOAuthHeaders();
        $postData = 'grant_type=client_credentials';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $this->accessToken = $data['access_token'];
            // Set expiry with 1 minute buffer (9 hours - 1 minute)
            $this->tokenExpiry = time() + ($data['expires_in'] - 60);

            return $this->accessToken;
        }

        throw new \Exception('Failed to get OAuth token: ' . $response);
    }

    /**
     * Make authenticated REST API request
     */
    protected function makeRequest($endpoint, $method = 'GET', $data = null)
    {
        $headers = $this->getHeaders(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($data && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'body' => json_decode($response, true),
            'raw_response' => $response
        ];
    }

    /**
     * Obtain the available balance for a PayPal account.
     *
     * @access  public
     * @return  mixed[] Returns an array structure of the PayPal HTTP response params as well as parsed balance results, errors and the raw request/response.
     */
    function GetBalance($DataArray)
    {
        $returnAllCurrencies = !empty($DataArray['GBFields']['returnallcurrencies']) ? true : false;

        $response = $this->makeRequest('/v1/reporting/balances');

        $responseSimplified = [
            'ASOFTIME' => !empty($response['body']['as_of_time']) ? $response['body']['as_of_time'] : '',
            'ACCOUNTID' => !empty($response['body']['account_id']) ? $response['body']['account_id'] : '',
            'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
            'ERRORS' => [],
            'BALANCES' => [],
            'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : '',
        ];

        // Capture errors if available
        if (!empty($response['body']['errors']) && is_array($response['body']['errors'])) {
            $responseSimplified['errors'] = $response['body']['errors'];
        }

        if (!empty($response['body']['balances']) && is_array($response['body']['balances'])) {
            foreach ($response['body']['balances'] as $balance) {
                if ($returnAllCurrencies || !empty($balance['primary'])) {
                    $responseSimplified['BALANCES'][] = [
                        'CURRENCY' => !empty($balance['currency']) ? $balance['currency'] : '',
                        'TOTALBALANCE' => !empty($balance['total_balance']['value']) ? $balance['total_balance']['value'] : 0,
                        'AVAILABLEBALANCE' => !empty($balance['available_balance']['value']) ? $balance['available_balance']['value'] : 0,
                        'WITHHELDBALANCE' => !empty($balance['withheld_balance']['value']) ? $balance['withheld_balance']['value'] : 0,
                        'PRIMARY' => !empty($balance['primary']) ? 1 : 0,
                    ];
                }
            }
        }

        return $responseSimplified;
    }

    function AddBankAccount($DataArray) {
        $AddBankAccountFields = isset($DataArray['AddBankAccountFields']) ? $DataArray['AddBankAccountFields'] : array();
        $WebOptions = isset($DataArray['WebOptions']) ? $DataArray['WebOptions'] : array();

        $payload = array(
            "account_number" => $AddBankAccountFields['BankAccountNumber'],
            "routing_number" => $AddBankAccountFields['RoutingNumber'],
            "account_type" => strtolower($AddBankAccountFields['BankAccountType']), // checking/savings
            "bank_name" => $AddBankAccountFields['BankName'],
            "country_code" => $AddBankAccountFields['BankCountryCode'],
            "account_holder_name" => $AddBankAccountFields['BankName'] . " Holder", // Example
            "confirmation_type" => strtoupper($AddBankAccountFields['ConfirmationType']), // WEB or NONE
            "metadata" => array(
                "email" => $AddBankAccountFields['EmailAddress'],
                "return_url" => $WebOptions['ReturnURL'],
                "cancel_url" => $WebOptions['CancelURL']
            )
        );

        // Step 3: Make REST Call
        $response = $this->makeRequest('/v1/vault/bank-accounts', 'POST', $payload);

        // Step 4: Handle Response
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return array(
                'success' => true,
                'status' => $response['status_code'],
                'response' => $response
            );
        } else {
            return array(
                'success' => false,
                'status' => $response['status_code'],
                'error' => $response
            );
        }
    }

    function AddPaymentCard($DataArray) {
        $AddPaymentCardFields = isset($DataArray['AddPaymentCardFields']) ? $DataArray['AddPaymentCardFields'] : array();
        $NameOnCard = isset($DataArray['NameOnCard']) ? $DataArray['NameOnCard'] : array();
        $BillingAddress = isset($DataArray['BillingAddress']) ? $DataArray['BillingAddress'] : array();
        $ExpirationDate = isset($DataArray['ExpirationDate']) ? $DataArray['ExpirationDate'] : array();
        $WebOptions = isset($DataArray['WebOptions']) ? $DataArray['WebOptions'] : array();

        $payload = array(
            'number' => $AddPaymentCardFields['CardNumber'],
            'type' => $AddPaymentCardFields['CardType'],
            'expire_month' => $ExpirationDate['Month'],
            'expire_year' => $ExpirationDate['Year'],
            'first_name' => $NameOnCard['FirstName'],
            'last_name' => $NameOnCard['LastName'],
            'billing_address' => array(
                'line1' => $BillingAddress['Line1'],
                'city' => $BillingAddress['City'],
                'state' => $BillingAddress['State'],
                'postal_code' => $BillingAddress['PostalCode'],
                'country_code' => $BillingAddress['CountryCode']
            )
        );

        // Step 3: Make REST Call
        $response = $this->makeRequest('/v1/vault/credit-cards', 'POST', $payload);

        $responseSimplified = [];

        // Step 4: Handle Response
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $responseSimplified = array(
                'SUCCESS' => true,
                'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        } else {
            $responseSimplified = array(
                'SUCCESS' => false,
                'STATUSCODE' => $response['status_code'],
                'ERRORS' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    function BMButtonSearch($DataArray) {
        $BMButtonSearchFields = isset($DataArray['BMButtonSearchFields']) ? $DataArray['BMButtonSearchFields'] : array();

        $response = $this->makeRequest('/v1/reporting/transactions?start_date=' . $BMButtonSearchFields['startdate'] . '&end_date=' . $BMButtonSearchFields['enddate']);

        $responseSimplified = [];

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $responseSimplified = array(
                'SUCCESS' => true,
                'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        } else {
            $responseSimplified = array(
                'SUCCESS' => false,
                'STATUSCODE' => $response['status_code'],
                'ERRORS' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    function BMGetButtonDetails($HostedButtonID) {
        $response = $this->makeRequest('/v1/checkout/orders/' . $HostedButtonID);

        $responseSimplified = [];

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $responseSimplified = array(
                'SUCCESS' => true,
                'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        } else {
            $responseSimplified = array(
                'SUCCESS' => false,
                'STATUSCODE' => $response['status_code'],
                'ERRORS' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    /**
     * Test OAuth authentication
     */
    public function testOAuth()
    {
        try {
            $token = $this->getAccessToken();
            return [
                'success' => true,
                'message' => 'OAuth token retrieved successfully',
                'token_preview' => substr($token, 0, 20) . '...',
                'expires_in' => $this->tokenExpiry - time()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'OAuth failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test simple API call (get webhook list - minimal permissions needed)
     */
    public function testAPICall()
    {
        try {
            $response = $this->makeRequest('/v1/notifications/webhooks');
            return [
                'success' => $response['status_code'] >= 200 && $response['status_code'] < 300,
                'message' => $response['status_code'] >= 200 && $response['status_code'] < 300
                    ? 'API call successful'
                    : 'API call failed',
                'status_code' => $response['status_code']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API call failed: ' . $e->getMessage(),
                'status_code' => 0
            ];
        }
    }

    /**
     * Create an order (replaces SetExpressCheckout)
     */
    public function createOrder($orderData)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders', 'POST', $orderData);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'order_id' => $response['body']['id'],
                    'status' => $response['body']['status'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create order',
                'status_code' => $response['status_code'],
                'details' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details (replaces GetExpressCheckoutDetails)
     */
    public function getOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId, 'GET');

            if ($response['status_code'] === 200) {
                return [
                    'success' => true,
                    'order' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get order details',
                'status_code' => $response['status_code'],
                'details' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Authorize order (for auth-only transactions)
     */
    public function authorizeOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/authorize', 'POST');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'authorization_id' => $response['body']['purchase_units'][0]['payments']['authorizations'][0]['id'],
                    'status' => $response['body']['status'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to authorize order',
                'status_code' => $response['status_code'],
                'details' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Capture order (replaces DoExpressCheckoutPayment)
     */
    public function captureOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/capture', 'POST');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'capture_id' => $response['body']['purchase_units'][0]['payments']['captures'][0]['id'],
                    'status' => $response['body']['status'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture order',
                'status_code' => $response['status_code'],
                'details' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Helper method to extract approval URL from links
     */
    private function getApprovalUrl($links)
    {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }

    public function CreatePayment($paymentData) {
        $paymentsMappedData = [];

        // Map basic payment info
        $paymentsMappedData['intent'] = strtolower($paymentData['Payments'][0]['paymentaction'] ?? 'sale');
        $paymentsMappedData['payer'] = [
            'payment_method' => 'paypal'
        ];

        // Redirect URLs
        $paymentsMappedData['redirect_urls'] = [
            'return_url' => $paymentData['SECFields']['returnurl'] ?? '',
            'cancel_url' => $paymentData['SECFields']['cancelurl'] ?? ''
        ];

        // Transaction
        $transaction = [
            'amount' => [
                'total' => $paymentData['Payments'][0]['amt'] ?? '0.00',
                'currency' => $paymentData['Payments'][0]['currencycode'] ?? 'USD',
                'details' => [
                    'subtotal' => $paymentData['Payments'][0]['itemamt'] ?? '0.00',
                    'tax' => $paymentData['Payments'][0]['taxamt'] ?? '0.00',
                    'shipping' => $paymentData['Payments'][0]['shippingamt'] ?? '0.00'
                ]
            ],
            'description' => $paymentData['Payments'][0]['desc'] ?? '',
            'item_list' => [
                'items' => []
            ]
        ];

        // Map order items
        if (!empty($paymentData['Payments'][0]['order_items'])) {
            foreach ($paymentData['Payments'][0]['order_items'] as $item) {
                $transaction['item_list']['items'][] = [
                    'name' => $item['name'] ?? '',
                    'sku' => $item['number'] ?? '',
                    'price' => $item['amt'] ?? '0.00',
                    'currency' => $paymentData['Payments'][0]['currencycode'] ?? 'USD',
                    'quantity' => $item['qty'] ?? 1
                ];
            }
        }

        $paymentsMappedData['transactions'][] = $transaction;
        
        $response = $this->makeRequest('/v1/payments/payment', 'POST', $paymentsMappedData);

        $responseSimplified = [];

        // Handle Response
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $responseSimplified = array(
                'SUCCESS' => true,
                'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        } else {
            $responseSimplified = array(
                'SUCCESS' => false,
                'STATUSCODE' => $response['status_code'],
                'ERRORS' => !empty($response['body']) ? $response['body'] : ['invalid_payment' => 'Payment execution failed'],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    public function ExecutePayment($paymentData) {
        $data = [
            'payer_id' => !empty($paymentData['payerID']) ? $paymentData['payerID'] : ''
        ];

        $paymentID = !empty($paymentData['paymentID']) ? $paymentData['paymentID'] : '';

        $response = $this->makeRequest('/v1/payments/payment/' . $paymentID . '/execute', 'POST', $data);
        
        $responseSimplified = [];

        // Handle Response
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $responseSimplified = array(
                'SUCCESS' => true,
                'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        } else {
            $responseSimplified = array(
                'SUCCESS' => false,
                'STATUSCODE' => $response['status_code'],
                'ERRORS' => !empty($response['body']) ? $response['body'] : ['invalid_payment' => 'Payment execution failed'],
                'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    /**
     * Test Orders API functionality
     */
    public function testOrdersAPI()
    {
        try {
            // Test data for creating an order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '10.00'
                        ],
                        'description' => 'Test order from PayPalREST class'
                    ]
                ],
                'application_context' => [
                    'return_url' => 'https://example.com/return',
                    'cancel_url' => 'https://example.com/cancel'
                ]
            ];

            echo "<h4>Step 1: Creating Order...</h4>";
            $createResult = $this->createOrder($orderData);

            if (!$createResult['success']) {
                return [
                    'success' => false,
                    'step_failed' => 'create_order',
                    'error' => $createResult['error']
                ];
            }

            $orderId = $createResult['order_id'];
            echo "Order Created Successfully<br>";
            echo "Order ID: " . $orderId . "<br>";
            echo "Status: " . $createResult['status'] . "<br>";
            echo "Approval URL: " . $createResult['approval_url'] . "<br><br>";

            // Test getting order details
            echo "<h4>Step 2: Getting Order Details...</h4>";
            $getResult = $this->getOrder($orderId);

            if (!$getResult['success']) {
                return [
                    'success' => false,
                    'step_failed' => 'get_order',
                    'error' => $getResult['error'],
                    'order_id' => $orderId
                ];
            }

            echo "Order Details Retrieved Successfully<br>";
            echo "Order Status: " . $getResult['order']['status'] . "<br>";
            echo "Order Amount: " . $getResult['order']['purchase_units'][0]['amount']['value'] . " " .
                $getResult['order']['purchase_units'][0]['amount']['currency_code'] . "<br><br>";

            return [
                'success' => true,
                'message' => 'Orders API test completed successfully',
                'order_id' => $orderId,
                'create_result' => $createResult,
                'get_result' => $getResult
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Orders API test failed: ' . $e->getMessage()
            ];
        }
    }

    // REST-specific methods will go here
    // OAuth, HTTP requests, etc.
}