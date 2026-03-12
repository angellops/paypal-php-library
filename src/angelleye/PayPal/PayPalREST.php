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
        // Call parent constructor first
        parent::__construct($config);

        // Override base URL for REST API endpoints
        $this->base_url = $this->Sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

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
    private function getHeaders($includeAuth = true, $contentType = 'application/json', $requestId = null)
    {
        $headers = [
            'Content-Type: ' . $contentType,
            'Accept: application/json',
            'PayPal-Partner-Attribution-Id: ' . $this->ButtonSource
        ];

        if ($includeAuth) {
            $token = $this->getAccessToken();
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if (!empty($requestId)) {
            $headers[] = 'PayPal-Request-Id: ' . $requestId;
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
            'PayPal-Partner-Attribution-Id: ' . $this->ButtonSource
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

        $auth = base64_encode($this->client_id . ':' . $this->client_secret);

        $headers = $this->getOAuthHeaders();
        $postData = 'grant_type=client_credentials';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

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
    protected function makeRequest($endpoint, $method = 'GET', $data = null, $requestId = null)
    {
        $headers = $this->getHeaders(true, 'application/json', $requestId);

        $url = $this->base_url . $endpoint;

        // Detect caller function
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerFunction = isset($trace[1]['function']) ? $trace[1]['function'] : 'unknown';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
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

        $request_log = [
            'request' => [
                'url' => $url,
                'method' => $method,
                'headers' => $headers,
                'payload' => $data
            ]
        ];

        // Log the request
        $this->Logger($this->LogPath, $callerFunction . 'Request', $request_log);

        return [
            'status_code' => $httpCode,
            'body' => json_decode($response, true),
            'raw_response' => $response
        ];
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
    public function createOrder($orderData, $paypalRequestId = null)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders', 'POST', $orderData, $paypalRequestId);

            // Log the response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201, 204])) {
                return [
                    'success' => true,
                    'order_id' => $response['body']['id'],
                    'status' => $response['body']['status'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create order',
                'status_code' => $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
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

            // Log the response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201, 204])) {
                return [
                    'success' => true,
                    'order' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get order details',
                'status_code' => $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
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

            // Log the response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201, 204])) {
                return [
                    'success' => true,
                    'authorization_id' => $response['body']['purchase_units'][0]['payments']['authorizations'][0]['id'],
                    'status' => $response['body']['status'],
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to authorize order',
                'status_code' => $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
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

            // Log the response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201, 204])) {
                return [
                    'success' => true,
                    'capture_id' => $response['body']['purchase_units'][0]['payments']['captures'][0]['id'],
                    'status' => $response['body']['status'],
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture order',
                'status_code' => $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
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
            if ($link['rel'] === 'approve' || $link['rel'] === 'payer-action') {
                return $link['href'];
            }
        }
        return null;
    }
    
    /**
     * Obtain the available balance for a PayPal account.
     *
     * @access  public
     * @return  mixed[] Returns an array structure of the PayPal HTTP response params as well as parsed balance results, errors and the raw request/response.
     */
    function getBalances()
    {
        try {
            $response = $this->makeRequest('/v1/reporting/balances');
            
            // call logger
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);
            
            if (in_array($response['status_code'], [200, 201])) {
                $body = $response['body'];
                
                // Normal REST-style response
                return [
                    'success' => true,
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $body,
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
    public function createSubscriptionProfile($DataArray)
    {
        $ProductData = !empty($DataArray['ProductData']) ? $DataArray['ProductData'] : [];
        $PlanData = !empty($DataArray['PlanData']) ? $DataArray['PlanData'] : [];
        $SubscriptionData = !empty($DataArray['SubscriptionData']) ? $DataArray['SubscriptionData'] : [];

        // Step 1: Create Product
        $product = $this->createProduct($ProductData);
        if (!$product['success']) {
            return $product;
        }

        // Step 2: Create Plan
        $plan = $this->createPlan($PlanData, $product['id']);
        if (!$plan['success']) {
            return $plan;
        }

        // Step 3: Create Subscription
        $subscription = $this->createSubscription($SubscriptionData, $plan['id']);

        return $subscription;
    }

    /**
     * Creates a product in the PayPal catalog.
     *
     * This method sends a POST request to the PayPal `/v1/catalogs/products` endpoint
     * to create a new product in the PayPal system.
     *
     * @param array $ProductData The product data to be sent in the request.
     * @return array The response from the PayPal API.
     */
    public function createProduct($ProductData)
    {
        $response = $this->makeRequest('/v1/catalogs/products', 'POST', $ProductData);

        // Log the response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response,
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
            'errors' => isset($response['body']) ? $response['body'] : [],
            'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
        ];
    }

    /**
     * Creates a billing plan in the PayPal system.
     *
     * This method sends a POST request to the PayPal `/v1/billing/plans` endpoint
     * to create a new billing plan.
     *
     * @param array $PlanData The plan data to be sent in the request.
     * @param string $productId The ID of the product associated with the plan.
     * @return array The response from the PayPal API.
     */
    public function createPlan($PlanData, $productId)
    {
        $PlanData['product_id'] = $productId;

        $response = $this->makeRequest('/v1/billing/plans', 'POST', $PlanData);

        // Log the response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response,
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
            'errors' => isset($response['body']) ? $response['body'] : [],
            'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
        ];
    }

    /**
     * Creates a subscription in the PayPal system.
     *
     * This method sends a POST request to the PayPal `/v1/billing/subscriptions` endpoint
     * to create a new subscription.
     *
     * @param array $SubscriptionData The subscription data to be sent in the request.
     * @param string $planId The ID of the plan associated with the subscription.
     * @return array The response from the PayPal API.
     */
    public function createSubscription($SubscriptionData, $planId)
    {
        $SubscriptionData['plan_id'] = $planId;

        $response = $this->makeRequest('/v1/billing/subscriptions', 'POST', $SubscriptionData);

        // Log the response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'subscription_id' => !empty($response['body']['id']) ? $response['body']['id'] : '',
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'approval_url' => $this->getApprovalUrl($response['body']['links']),
                'response' => !empty($response['body']) ? $response['body'] : [],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
            'errors' => !empty($response['body']) ? $response['body'] : [],
            'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
        ];
    }

    /**
     * Retrieves details of a PayPal subscription profile using the REST API.
     *
     * This method sends a GET request to the PayPal `/v1/billing/subscriptions/{subscription_id}` 
     * endpoint to fetch information about a specific subscription, including its status and 
     * associated details.
     */
    public function getSubscriptionProfile($subscriptionID) {
        try {
            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionID, 'GET');

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => !empty($response['body']) ? $response['body'] : [],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
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
    public function manageSubscriptionProfile($DataArray) {
        try {
            $subscriptionId = isset($DataArray['subscription_id']) ? $DataArray['subscription_id'] : '';
            $subscriptionAction = isset($DataArray['subscription_action']) ? strtolower($DataArray['subscription_action']) : '';
            $subscriptionReason = isset($DataArray['subscription_reason']) ? array('reason' => $DataArray['subscription_reason']) : array();

            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionId . '/' . $subscriptionAction, 'POST', $subscriptionReason);
            
            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                return [
                    'success' => true,
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
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
    public function updateSubscriptionProfile($DataArray) {
        try {
            $subscriptionId = isset($DataArray['subscription_id']) ? $DataArray['subscription_id'] : '';
            $patches = isset($DataArray['patches']) ? $DataArray['patches'] : array();

            $response = $this->makeRequest('/v1/billing/subscriptions/' . $subscriptionId, 'PATCH', $patches);

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                return [
                    'success' => true,
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
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
    public function getPayPalUserInfo() {
        try {
            $response = $this->makeRequest('/v1/identity/oauth2/userinfo?schema=paypalv1.1');

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => $response['body'],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
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
