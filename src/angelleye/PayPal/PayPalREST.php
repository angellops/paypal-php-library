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
    private function getHeaders($includeAuth = true, $contentType = 'application/json', $requestId = null, $isInvoiceRequest = false)
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

        if (!empty($requestId)) {
            $headers[] = 'PayPal-Request-Id: ' . $requestId;
        }

        if ($isInvoiceRequest) {
            $headers[] = 'Prefer: return=representation';
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
    protected function makeRequest($endpoint, $method = 'GET', $data = null, $requestId = null, $isInvoiceRequest = false)
    {
        $headers = $this->getHeaders(true, 'application/json', $requestId, $isInvoiceRequest);

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
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to authorize order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Capture Authorized order
     */
    public function captureAutorizedOrder($authorizationId)
    {
        try {
            $response = $this->makeRequest('/v2/payments/authorizations/' . $authorizationId . '/capture', 'POST');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'capture_id' => $response['body']['id'],
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Capture Authorized order
     */
    public function getCapturedOrderDetails($captureId)
    {
        try {
            $response = $this->makeRequest('/v2/payments/captures/' . $captureId, 'GET');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => $response['body']
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

    public function DoDirectPayment($paymentData) {
        $paymentsMappedData = [];

        $paymentsMappedData['intent'] = !empty($paymentData['DPFields']['paymentaction']) ? $paymentData['DPFields']['paymentaction'] : 'sale';
        $paymentsMappedData['purchase_units'] = [
            [
                'amount' => [
                    'currency_code' => !empty($paymentData['PaymentDetails']['currencycode']) ? $paymentData['PaymentDetails']['currencycode'] : '',
                    'value' => !empty($paymentData['PaymentDetails']['amt']) ? $paymentData['PaymentDetails']['amt'] : 0.00,
                ]
            ]
        ];

        $paymentsMappedData['payment_source'] = [
            'card' => [
                'number' => !empty($paymentData['CCDetails']['acct']) ? $paymentData['CCDetails']['acct'] : '',
                'expiry' => !empty($paymentData['CCDetails']['expdate']) ? $paymentData['CCDetails']['expdate'] : '',
                'security_code' => !empty($paymentData['CCDetails']['cvv2']) ? $paymentData['CCDetails']['cvv2'] : '',
                'name' => (!empty($paymentData['PayerName']['firstname']) ? $paymentData['PayerName']['firstname'] : '') . ' ' . (!empty($paymentData['PayerName']['lastname']) ? $paymentData['PayerName']['lastname'] : ''),
                'billing_address' => [
                    'address_line_1' => !empty($paymentData['BillingAddress']['street']) ? $paymentData['BillingAddress']['street'] : '',
                    'admin_area_2' => !empty($paymentData['BillingAddress']['city']) ? $paymentData['BillingAddress']['city'] : '',
                    'admin_area_1' => !empty($paymentData['BillingAddress']['state']) ? $paymentData['BillingAddress']['state'] : '',
                    'postal_code' => !empty($paymentData['BillingAddress']['zip']) ? $paymentData['BillingAddress']['zip'] : '',
                    'country_code' => !empty($paymentData['BillingAddress']['countrycode']) ? $paymentData['BillingAddress']['countrycode'] : ''
                ]
            ]  
        ];

        $paypalRequestId = uniqid('pprid_', true);

        $response = $this->makeRequest('/v2/checkout/orders', 'POST', $paymentsMappedData, $paypalRequestId);

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
     * Create a recurring payments profile using PayPal REST APIs.
     *
     * This method automates the process of creating a PayPal product, 
     * billing plan, and subscription (recurring profile) in sequence.
     *
     * Steps:
     * 1. Create a product using the `/v1/catalogs/products` endpoint.
     * 2. Create a billing plan linked to the product using `/v1/billing/plans`.
     * 3. Create a subscription (recurring payments profile) using `/v1/billing/subscriptions`.
     */
    public function CreateSubscriptionProfile($DataArray) {
        // Extract the data arrays or initialize as empty arrays
        $ProductData = isset($DataArray['ProductData']) ? $DataArray['ProductData'] : array();
        $PlanData = isset($DataArray['PlanData']) ? $DataArray['PlanData'] : array();
        $SubscriptionData = isset($DataArray['SubscriptionData']) ? $DataArray['SubscriptionData'] : array();

        // Step 1: Create the product in PayPal Catalog
        $productResponse = $this->makeRequest('/v1/catalogs/products', 'POST', $ProductData);

        $responseSimplified = array();

        // Check if product creation succeeded (status 2xx)
        if ($productResponse['status_code'] >= 200 && $productResponse['status_code'] < 300) {
            // Attach created product_id to the plan data
            $PlanData['product_id'] = isset($productResponse['body']['id']) ? $productResponse['body']['id'] : '';

            // Step 2: Create a billing plan associated with the product
            $planResponse = $this->makeRequest('/v1/billing/plans', 'POST', $PlanData);

            if ($planResponse['status_code'] >= 200 && $planResponse['status_code'] < 300) {
                // Attach the created plan_id to the subscription data
                $SubscriptionData['plan_id'] = isset($planResponse['body']['id']) ? $planResponse['body']['id'] : '';

                // Step 3: Create a subscription (recurring payments profile)
                $subscriptionResponse = $this->makeRequest('/v1/billing/subscriptions', 'POST', $SubscriptionData);

                if ($subscriptionResponse['status_code'] >= 200 && $subscriptionResponse['status_code'] < 300) {
                    $responseSimplified = array(
                        'success' => true,
                        'subscription_id' => !empty($subscriptionResponse['body']['id']) ? $subscriptionResponse['body']['id'] : '',
                        'status' => !empty($subscriptionResponse['status_code']) ? $subscriptionResponse['status_code'] : 0,
                        'response' => !empty($subscriptionResponse['body']) ? $subscriptionResponse['body'] : [],
                        'raw_response' => !empty($subscriptionResponse['raw_response']) ? $subscriptionResponse['raw_response'] : [],
                    );
                } else {
                    $responseSimplified = array(
                        'success' => false,
                        'status' => $subscriptionResponse['status_code'],
                        'error' => !empty($subscriptionResponse['body']) ? $subscriptionResponse['body'] : [],
                        'raw_response' => !empty($subscriptionResponse['raw_response']) ? $subscriptionResponse['raw_response'] : [],
                    );
                }
            } else {
                $responseSimplified = array(
                    'success' => false,
                    'status' => $planResponse['status_code'],
                    'error' => !empty($planResponse['body']) ? $planResponse['body'] : [],
                    'raw_response' => !empty($planResponse['raw_response']) ? $planResponse['raw_response'] : [],
                );
            }
        } else {
            $responseSimplified = array(
                'success' => false,
                'status' => $productResponse['status_code'],
                'error' => !empty($productResponse['body']) ? $productResponse['body'] : [],
                'raw_response' => !empty($productResponse['raw_response']) ? $productResponse['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    /**
     * Retrieves details of a PayPal subscription profile using the REST API.
     *
     * This method sends a GET request to the PayPal `/v1/billing/subscriptions/{subscription_id}` 
     * endpoint to fetch information about a specific subscription, including its status and 
     * associated details.
     */
    public function GetSubscriptionProfile($DataArray) {
        try {
            $subscriptionId = isset($DataArray['subscription_id']) ? $DataArray['subscription_id'] : '';

            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionId, 'GET');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Manage a PayPal subscription profile by performing actions such as cancel, suspend, or activate.
     *
     * This function interacts with the PayPal REST API to manage an existing subscription.
     * Supported actions typically include `cancel`, `suspend`, or `activate`.
     */
    public function ManageSubscriptionProfile($DataArray) {
        try {
            $subscriptionId = isset($DataArray['subscription_id']) ? $DataArray['subscription_id'] : '';
            $subscriptionAction = isset($DataArray['subscription_action']) ? strtolower($DataArray['subscription_action']) : '';
            $subscriptionReason = isset($DataArray['subscription_reason']) ? array('reason' => $DataArray['subscription_reason']) : array();

            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionId . '/' . $subscriptionAction, 'POST', $subscriptionReason);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing PayPal subscription profile using PATCH request.
     *
     * This method sends a PATCH request to the PayPal REST API endpoint
     * `/v1/billing/subscriptions/{subscription_id}` to update details of an active subscription,
     * such as plan, quantity, or metadata.
     */
    public function UpdateSubscriptionProfile($DataArray) {
        try {
            $subscriptionId = isset($DataArray['subscription_id']) ? $DataArray['subscription_id'] : '';
            $patches = isset($DataArray['patches']) ? $DataArray['patches'] : array();

            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionId, 'PATCH', $patches);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reauthorizes an existing PayPal payment authorization.
     *
     * This method sends a POST request to the PayPal REST API endpoint
     * `/v2/payments/authorizations/{authorization_id}/reauthorize` to reauthorize
     * a previously authorized payment before it expires.
     */
    public function UpdateAuthorization($transactionId)
    {
        try {
            $response = $this->makeRequest('/v2/payments/authorizations/' . $transactionId . '/reauthorize', 'POST');

            if ($response['status_code'] === 200) {
                return [
                    'success' => true,
                    'order' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get order details',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Issues a refund for a captured PayPal payment.
     *
     * This method sends a POST request to the PayPal REST API endpoint
     * `/v2/payments/captures/{transaction_id}/refund` to process a full or partial refund
     * for a completed payment capture.
     */
    public function Refund($DataArray) {
        try {
            $transactionId = isset($DataArray['transaction_id']) ? $DataArray['transaction_id'] : '';
            $refundFields = isset($DataArray['refund_fields']) ? $DataArray['refund_fields'] : array();

            $response = $this->makeRequest('/v2/payments/captures/' . $transactionId . '/refund', 'POST', $refundFields);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Executes a PayPal Mass Payment (Payout) request using the REST API.
     *
     * This function sends a POST request to the PayPal Payouts endpoint (`/v1/payments/payouts`)
     * with the provided payout data array. It returns the response status, success flag,
     * and the full API response for further inspection.
     */
    public function MassPay($DataArray) {
        try {
            $response = $this->makeRequest('/v1/payments/payouts', 'POST', $DataArray);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves PayPal account details of the authenticated user using the REST Identity API.
     *
     * This method calls the `/v1/identity/oauth2/userinfo?schema=paypalv1.1` endpoint
     * to get information about the merchant’s PayPal account such as email, account ID,
     * verification status, and country. It serves as the REST equivalent of the classic
     * NVP `GetPalDetails` API.
     */
    public function GetPalDetails() {
        try {
            $response = $this->makeRequest('/v1/identity/oauth2/userinfo?schema=paypalv1.1');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves user information using the PayPal REST Identity API.
     *
     * This method sends a request to the PayPal endpoint `/v1/identity/oauth2/userinfo?schema=openid`
     * to obtain user details associated with the OAuth2 token. It processes the API response and
     * returns success or failure based on the HTTP status code.
     */
    public function RequestPermissions() {  
        try {
            $response = $this->makeRequest('/v1/identity/oauth2/userinfo?schema=openid');

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status' => $response['body']['status'] ?? $response['status_code'],
                'full_response' => $response['body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new invoice using the PayPal Invoicing API (v2).
     *
     * This method sends a POST request to the `/v2/invoicing/invoices` endpoint with the provided
     * invoice data. If successful, it returns the created invoice's ID, status, and full response.
     */
    public function CreateInvoice($InvoiceData)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices', 'POST', $InvoiceData, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'invoice_id' => $response['body']['id'] ?? null,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Sends a PayPal invoice to the recipient using the PayPal Invoicing API (v2).
     *
     * This method triggers the sending of an existing draft invoice identified by its Invoice ID.
     * The API endpoint `/v2/invoicing/invoices/{invoice_id}/send` is used to send the invoice
     * to the customer via email.
     */
    public function SendInvoice($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/send', 'POST', null, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Create and send a PayPal invoice using the PayPal Invoicing REST API (v2).
     *
     * This method first creates a new invoice using the provided invoice data, then immediately sends
     * the created invoice to the recipient. It performs both API calls sequentially:
     *  1. Create the invoice via `/v2/invoicing/invoices`
     *  2. Send the created invoice via `/v2/invoicing/invoices/{invoice_id}/send`
     */
    public function CreateAndSendInvoice($InvoiceData)
    {
        $createInvoiceResponse = $this->makeRequest('/v2/invoicing/invoices', 'POST', $InvoiceData, null, true);

        $responseSimplified = array();

        // Check if product creation succeeded (status 2xx)
        if ($createInvoiceResponse['status_code'] >= 200 && $createInvoiceResponse['status_code'] < 300) {
            // Attach created product_id to the plan data
            $InvoiceID = isset($createInvoiceResponse['body']['id']) ? $createInvoiceResponse['body']['id'] : '';

            // Step 2: Create a billing plan associated with the product
            $sendInvoiceResponse = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/send', 'POST', null, null, true);

            if ($sendInvoiceResponse['status_code'] >= 200 && $sendInvoiceResponse['status_code'] < 300) {
                $responseSimplified = array(
                    'success' => true,
                    'status' => !empty($sendInvoiceResponse['status_code']) ? $sendInvoiceResponse['status_code'] : 0,
                    'response' => !empty($sendInvoiceResponse['body']) ? $sendInvoiceResponse['body'] : [],
                    'raw_response' => !empty($sendInvoiceResponse['raw_response']) ? $sendInvoiceResponse['raw_response'] : [],
                );
            } else {
                $responseSimplified = array(
                    'success' => false,
                    'status' => $sendInvoiceResponse['status_code'],
                    'error' => !empty($sendInvoiceResponse['body']) ? $sendInvoiceResponse['body'] : [],
                    'raw_response' => !empty($sendInvoiceResponse['raw_response']) ? $sendInvoiceResponse['raw_response'] : [],
                );
            }
        } else {
            $responseSimplified = array(
                'success' => false,
                'status' => $createInvoiceResponse['status_code'],
                'error' => !empty($createInvoiceResponse['body']) ? $createInvoiceResponse['body'] : [],
                'raw_response' => !empty($createInvoiceResponse['raw_response']) ? $createInvoiceResponse['raw_response'] : [],
            );
        }

        return $responseSimplified;
    }

    /**
     * Retrieves detailed information about a specific PayPal invoice.
     *
     * This method calls the PayPal REST API endpoint `/v2/invoicing/invoices/{invoice_id}`
     * to fetch full invoice details including status, amount, payer, and metadata.
     */
    public function GetInvoiceDetails($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID, 'GET', null, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Cancels an existing PayPal invoice.
     *
     * This method sends a POST request to the PayPal REST API endpoint 
     * `/v2/invoicing/invoices/{invoice_id}/cancel` to cancel a specific invoice.
     * Optionally, it can send an email notification to both the payer and the merchant.
     */
    public function CancelInvoice($InvoiceData)
    {
        try {
            $payload = [
                'subject' => $InvoiceData['Subject'] ?? 'Invoice has been canceled.',
                'note' => $InvoiceData['NoteForPayer'] ?? 'The invoice has been canceled by the merchant.',
                'send_to_invoicer' => isset($InvoiceData['SendCopyToMerchant'])
                    ? filter_var($InvoiceData['SendCopyToMerchant'], FILTER_VALIDATE_BOOLEAN)
                    : true
            ];

            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/cancel', 'POST', $payload, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice canceled successfully which may not return a body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice canceled successfully which may not return a body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes an existing invoice from PayPal using the REST API (v2).
     *
     * This method sends a DELETE request to the PayPal Invoicing API endpoint to permanently 
     * remove an invoice identified by the provided Invoice ID. 
     * 
     * Note: A successful DELETE operation may return an empty response body.
     */
    public function DeleteInvoice($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID, 'DELETE', null, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice deleted successfully which may not return a body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice deleted successfully which may not return a body']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Marks a PayPal invoice as paid using the REST API (v2).
     *
     * This method records a payment against an existing PayPal invoice by sending
     * a POST request to the `/v2/invoicing/invoices/{invoice_id}/payments` endpoint.
     * 
     * The provided payload should contain payment details such as the method,
     * transaction ID, and payment date. A successful operation marks the invoice
     * as paid and updates its status accordingly.
     */
    public function MarkInvoiceAsPaid($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['MarkInvoiceAsPaidFields']) ? $InvoiceData['MarkInvoiceAsPaidFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/payments', 'POST', $payload, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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
     * Marks a PayPal invoice as refunded using the REST API.
     *
     * This method sends a POST request to the PayPal Invoicing API to record a refund 
     * against a specific invoice. It requires the invoice ID and refund details 
     * such as amount, note, or refund type (if applicable) in the payload.
     */
    public function MarkInvoiceAsRefunded($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['MarkInvoiceAsRefundedFields']) ? $InvoiceData['MarkInvoiceAsRefundedFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/refunds', 'POST', $payload, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
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