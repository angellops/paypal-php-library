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
    private $merchant_id;
    private $print_headers;
    private $base_url;
    protected string $requiredMode = 'rest';

    public function __construct($config)
    {
        // Call parent constructor first
        parent::__construct($config);

        // Override base URL for REST API endpoints
        $this->base_url = $this->Sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        // Set REST-specific credentials
        $this->client_id = isset($config['ClientID']) ? $config['ClientID'] : '';
        $this->client_secret = isset($config['ClientSecret']) ? $config['ClientSecret'] : '';
        $this->merchant_id = isset($config['MerchantID']) ? $config['MerchantID'] : '';
        $this->print_headers = isset($config['PrintHeaders']) ? $config['PrintHeaders'] : false;

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
    private function getHeaders($includeAuth = true, $contentType = 'application/json', $requestId = null, $isInvoiceRequest = false, $includeAuthAssertion = true)
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

        if ($includeAuthAssertion) {
            $headers[] = 'PayPal-Auth-Assertion: ' . $this->paypalAuthAssertion();
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
            'PayPal-Partner-Attribution-Id: ' . $this->ButtonSource
        ];
    }

    /**
     * Generate a PayPal-Auth-Assertion token.
     *
     * @access  public
     * @return  string  A dot-separated JWT string containing the header and payload.
     */
    private function paypalAuthAssertion()
    {
        $temp = array(
            "alg" => "none"
        );
        $returnData = base64_encode(json_encode($temp)) . '.';
        $temp = array(
            "iss" => $this->client_id,
            "payer_id" => $this->merchant_id
        );
        $returnData .= base64_encode(json_encode($temp)) . '.';
        return $returnData;
    }

    /**
     * Parse raw HTTP header string into an associative array.
     */
    private function parseHeaders($headerString)
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Get OAuth 2.0 access token
     * Caches token for 9 hours to avoid redundant API calls
     */
    private function getAccessToken( $load_sdk_btn = false )
    {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        $auth = base64_encode($this->client_id . ':' . $this->client_secret);

        $headers = $this->getOAuthHeaders();
        $postData = ['grant_type' => 'client_credentials'];

        if ($load_sdk_btn) {
            $postData['response_type'] = 'client_token';
        }

        $url = $this->base_url . '/v1/oauth2/token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($this->print_headers) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $parsedHeaders = [];
        $debugId = null;
        if ($this->print_headers) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerString = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $parsedHeaders = $this->parseHeaders($headerString);
            $debugId = !empty($parsedHeaders['paypal-debug-id']) ? $parsedHeaders['paypal-debug-id'] : null;
        } else {
            $body = $response;
        }
        curl_close($ch);

        $tokenType = $load_sdk_btn ? 'ClientToken' : 'AccessToken';
        $tokenSource = $load_sdk_btn ? 'paypal_js_sdk' : 'server_api';

        // Request Log
        $request_log = [
            'type' => $tokenType,
            'source' => $tokenSource,
            'url' => $url,
            'method' => 'POST',
            'headers' => $headers,
            'payload' => $postData
        ];
        $this->Logger($this->LogPath, $tokenType . 'Request', $request_log);

        // Response Log
        $response_log = [
            'type' => $tokenType,
            'source' => $tokenSource,
            'status_code' => $httpCode,
            'debug_id' => $debugId,
            'headers' => $parsedHeaders,
            'body' => json_decode($body, true)
        ];
        $this->Logger($this->LogPath, $tokenType . 'Response', $response_log);

        if ($httpCode === 200) {
            $data = json_decode($body, true);
            $this->accessToken = $data['access_token'];
            // Set expiry with 1 minute buffer (9 hours - 1 minute)
            $this->tokenExpiry = time() + ($data['expires_in'] - 60);

            return $this->accessToken;
        }

        throw new \Exception('Failed to get OAuth token: ' . $body);
    }

    /**
     * Make authenticated REST API request
     */
    protected function makeRequest($endpoint, $method = 'GET', $data = null, $requestId = null, $isInvoiceRequest = false, $includeAuth = false)
    {
        $headers = $this->getHeaders(true, 'application/json', $requestId, $isInvoiceRequest, $includeAuth);

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

        if($this->print_headers) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        if ($data && in_array($method, ['POST','PUT','PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $parsedHeaders = [];
        $debugId = null;
        if ($this->print_headers) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerString = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $parsedHeaders = $this->parseHeaders($headerString);
            $debugId = !empty($parsedHeaders['paypal-debug-id']) ? $parsedHeaders['paypal-debug-id'] : null;
        } else {
            $body = $response;
        }
        curl_close($ch);

        // Request Log
        $request_log = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'payload' => $data,
            'request_id' => $requestId
        ];
        $this->Logger($this->LogPath, $callerFunction . 'Request', $request_log);

        // Response Log
        $response_log = [
            'status_code' => $httpCode,
            'debug_id' => $debugId,
            'headers' => $parsedHeaders,
            'body' => json_decode($body, true)
        ];
        $this->Logger($this->LogPath, $callerFunction . 'Response', $response_log);

        return [
            'status_code' => $httpCode,
            'debug_id' => $debugId,
            'headers' => $parsedHeaders,
            'body' => json_decode($body, true),
            'raw_response' => $body
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'order_id' => $response['body']['id'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'error' => 'Failed to create order',
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'order' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'error' => 'Failed to get order details',
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'authorization_id' => $response['body']['purchase_units'][0]['payments']['authorizations'][0]['id'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'error' => 'Failed to authorize order',
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'capture_id' => $response['body']['purchase_units'][0]['payments']['captures'][0]['id'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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
     * Reauthorize a previously authorized PayPal transaction.
     */
    public function updateAuthorization($transactionID)
    {   
        try {
            $response = $this->makeRequest('/v2/payments/authorizations/' . $transactionID . '/reauthorize', 'POST');

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'order' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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
     * Executes a PayPal Payouts (Mass Payments) request via REST API.
     */
    public function massPayments($DataArray)
    {
        try {
            $response = $this->makeRequest('/v1/payments/payouts', 'POST', $DataArray);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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
     * Issues a refund for a captured PayPal payment.
     *
     * This method sends a POST request to the PayPal REST API endpoint
     * `/v2/payments/captures/{transaction_id}/refund` to process a full or partial refund
     * for a completed payment capture.
     */
    public function refundPayments($DataArray)
    {
        try {
            $transactionId = isset($DataArray['transaction_id']) ? $DataArray['transaction_id'] : '';
            $refundType = isset($DataArray['refund_type']) ? $DataArray['refund_type'] : 'full';
            $refundFields = isset($DataArray['refund_fields']) ? $DataArray['refund_fields'] : [];
            if( !empty($refundType) && $refundType === 'full' ) {
                $refundFields = [];
            }

            $response = $this->makeRequest('/v2/payments/captures/' . $transactionId . '/refund', 'POST', $refundFields);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                $refundTransactionID = !empty($response['body']['id']) ? $response['body']['id'] : '';
                
                return $this->getRefundPaymentsDetails($refundTransactionID);
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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
     * Issues a refund for a captured PayPal payment.
     *
     * This method sends a POST request to the PayPal REST API endpoint
     * `/v2/payments/captures/{transaction_id}/refund` to process a full or partial refund
     * for a completed payment capture.
     */
    public function getRefundPaymentsDetails($transactionId)
    {
        try {
            $response = $this->makeRequest('/v2/payments/refunds/' . $transactionId);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'refund_id' => !empty($response['body']['id']) ? $response['body']['id'] : '',
                    'full_response' => $response['body'],
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
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
     * Obtain the available balance for a PayPal account.
     *
     * @access  public
     * @return  mixed[] Returns an array structure of the PayPal HTTP response params as well as parsed balance results, errors and the raw request/response.
     */
    public function getBalances()
    {
        try {
            $response = $this->makeRequest('/v1/reporting/balances');
            
            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                $body = $response['body'];
                
                // Normal REST-style response
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $body,
                    'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'headers' => $response['headers'],
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response,
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'headers' => $response['headers'],
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

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'headers' => $response['headers'],
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response,
                'raw_response' => isset($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'headers' => $response['headers'],
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

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'success' => true,
                'headers' => $response['headers'],
                'subscription_id' => !empty($response['body']['id']) ? $response['body']['id'] : '',
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'approval_url' => $this->getApprovalUrl($response['body']['links']),
                'response' => !empty($response['body']) ? $response['body'] : [],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            ];
        }

        return [
            'success' => false,
            'headers' => $response['headers'],
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
            
            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
     * Creates a new invoice using the PayPal Invoicing API (v2).
     *
     * This method sends a POST request to the `/v2/invoicing/invoices` endpoint with the provided
     * invoice data. If successful, it returns the created invoice's ID, status, and full response.
     */
    public function createInvoice($InvoiceData)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices', 'POST', $InvoiceData, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'invoice_id' => !empty($response['body']['id']) ? $response['body']['id'] : null,
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
     * Sends a PayPal invoice to the recipient using the PayPal Invoicing API (v2).
     *
     * This method triggers the sending of an existing draft invoice identified by its Invoice ID.
     * The API endpoint `/v2/invoicing/invoices/{invoice_id}/send` is used to send the invoice
     * to the customer via email.
     */
    public function sendInvoice($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/send', 'POST', null, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
     * Create and send a PayPal invoice using the PayPal Invoicing REST API (v2).
     *
     * This method first creates a new invoice using the provided invoice data, then immediately sends
     * the created invoice to the recipient. It performs both API calls sequentially:
     *  1. Create the invoice via `/v2/invoicing/invoices`
     *  2. Send the created invoice via `/v2/invoicing/invoices/{invoice_id}/send`
     */
    public function createAndSendInvoice($InvoiceData)
    {
        try {
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
                        'headers' => $sendInvoiceResponse['headers'],
                        'status' => !empty($sendInvoiceResponse['body']['status']) ? $sendInvoiceResponse['body']['status'] : $sendInvoiceResponse['status_code'],
                        'response' => !empty($sendInvoiceResponse['body']) ? $sendInvoiceResponse['body'] : [],
                        'raw_response' => !empty($sendInvoiceResponse['raw_response']) ? $sendInvoiceResponse['raw_response'] : [],
                    );
                } else {
                    $responseSimplified = array(
                        'success' => false,
                        'headers' => $sendInvoiceResponse['headers'],
                        'status' => !empty($sendInvoiceResponse['body']['status']) ? $sendInvoiceResponse['body']['status'] : $sendInvoiceResponse['status_code'],
                        'errors' => !empty($sendInvoiceResponse['body']) ? $sendInvoiceResponse['body'] : [],
                        'raw_response' => !empty($sendInvoiceResponse['raw_response']) ? $sendInvoiceResponse['raw_response'] : [],
                    );
                }
            } else {
                $responseSimplified = array(
                    'success' => false,
                    'headers' => $createInvoiceResponse['headers'],
                    'status' => !empty($createInvoiceResponse['body']['status']) ? $createInvoiceResponse['body']['status'] : $createInvoiceResponse['status_code'],
                    'errors' => !empty($createInvoiceResponse['body']) ? $createInvoiceResponse['body'] : [],
                    'raw_response' => !empty($createInvoiceResponse['raw_response']) ? $createInvoiceResponse['raw_response'] : [],
                );
            }

            return $responseSimplified;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves detailed information about a specific PayPal invoice.
     *
     * This method calls the PayPal REST API endpoint `/v2/invoicing/invoices/{invoice_id}`
     * to fetch full invoice details including status, amount, payer, and metadata.
     */
    public function getInvoiceDetails($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID, 'GET', null, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
     * Cancels an existing PayPal invoice.
     *
     * This method sends a POST request to the PayPal REST API endpoint 
     * `/v2/invoicing/invoices/{invoice_id}/cancel` to cancel a specific invoice.
     * Optionally, it can send an email notification to both the payer and the merchant.
     */
    public function cancelInvoice($InvoiceData)
    {
        try {
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';
            $PayloadData = isset($InvoiceData['PayloadData']) ? $InvoiceData['PayloadData'] : [];

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/cancel', 'POST', $PayloadData, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice canceled successfully which may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice canceled successfully which may not return a body'],
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
     * Deletes an existing invoice from PayPal using the REST API (v2).
     *
     * This method sends a DELETE request to the PayPal Invoicing API endpoint to permanently 
     * remove an invoice identified by the provided Invoice ID. 
     * 
     * Note: A successful DELETE operation may return an empty response body.
     */
    public function deleteInvoice($InvoiceID)
    {
        try {
            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID, 'DELETE', null, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice deleted successfully which may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'errors' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice deleted successfully which may not return a body'],
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
     * Marks a PayPal invoice as paid using the REST API (v2).
     *
     * This method records a payment against an existing PayPal invoice by sending
     * a POST request to the `/v2/invoicing/invoices/{invoice_id}/payments` endpoint.
     * 
     * The provided payload should contain payment details such as the method,
     * transaction ID, and payment date. A successful operation marks the invoice
     * as paid and updates its status accordingly.
     */
    public function markInvoiceAsPaid($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['MarkInvoiceAsPaidFields']) ? $InvoiceData['MarkInvoiceAsPaidFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/payments', 'POST', $payload, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
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
     * Marks a PayPal invoice as refunded using the REST API.
     *
     * This method sends a POST request to the PayPal Invoicing API to record a refund 
     * against a specific invoice. It requires the invoice ID and refund details 
     * such as amount, note, or refund type (if applicable) in the payload.
     */
    public function markInvoiceAsRefunded($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['MarkInvoiceAsRefundedFields']) ? $InvoiceData['MarkInvoiceAsRefundedFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/refunds', 'POST', $payload, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'details' => $response['body'],
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
     * Sends a reminder for a specific invoice using the PayPal REST API.
     *
     * This method triggers a reminder email to the recipient of the specified invoice.
     * It makes a POST request to the `/v2/invoicing/invoices/{invoice_id}/remind` endpoint.
     */
    public function remindInvoice($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['RemindInvoiceFields']) ? $InvoiceData['RemindInvoiceFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/remind', 'POST', $payload, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                return [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice reminder sent successfully which may not return a body'],
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'details' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice reminder sent successfully which may not return a body'],
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
     * Searches and filters PayPal invoices using REST API.
     *
     * This method retrieves a paginated list of invoices from the PayPal Invoicing API
     * and applies optional filters such as status, email, currency, amount range, and memo.
     * It supports client-side filtering using the provided `$Parameters` array.
     */
    public function searchInvoices($InvoiceData)
    {
        try {
            $SearchInvoicesFields = !empty($InvoiceData['SearchInvoicesFields']) ? $InvoiceData['SearchInvoicesFields'] : [];
            $Parameters = !empty($InvoiceData['Parameters']) ? $InvoiceData['Parameters'] : [];

            $page = isset($SearchInvoicesFields['Page']) ? $SearchInvoicesFields['Page'] : 1;
            $page_size = isset($SearchInvoicesFields['PageSize']) ? $SearchInvoicesFields['PageSize'] : 20;

            // Build query parameters for filtering
            $query = [];

            // Pagination parameters
            $query['page'] = $page;
            $query['page_size'] = $page_size;

            // Build the query string
            $query_string = http_build_query($query);

            // Make REST API request
            $response = $this->makeRequest('/v2/invoicing/invoices?' . $query_string, 'GET', null, null, true);

            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                $invoices = $response['body']['items'];

                $filteredInvoices = array_filter($invoices, function ($invoice) use ($Parameters) {
                    $statusMatch = true;
                    $emailMatch = true;
                    $invoiceEmailMatch = true;
                    $invoiceNumberMatch = true;
                    $currencyMatch = true;
                    $amountMatch = true;
                    $memoMatch = true;

                    // Filter: Status
                    if (!empty($Parameters['Status'])) {
                        $statusMatch = isset($invoice['status']) && strtoupper($invoice['status']) === strtoupper($Parameters['Status']);
                    }

                    // Filter: Email
                    if (!empty($Parameters['Email'])) {
                        $recipientEmail = $invoice['primary_recipients'][0]['billing_info']['email_address'] ?? '';
                        $emailMatch = strcasecmp($recipientEmail, $Parameters['Email']) === 0;
                    }

                    // Filter: Invoicer email
                    if (!empty($Parameters['MerchantEmail'])) {
                        $invoicerEmail = $invoice['invoicer']['email_address'] ?? '';
                        $emailMatch = strcasecmp($invoicerEmail, $Parameters['MerchantEmail']) === 0;
                    }

                    // Filter: Invoice number
                    if (!empty($Parameters['InvoiceNumber'])) {
                        $invoiceNumberMatch = isset($invoice['detail']['invoice_number']) && $invoice['detail']['invoice_number'] == $Parameters['InvoiceNumber'];
                    }

                    // Filter: Currency code
                    if (!empty($Parameters['CurrencyCode'])) {
                        $currencyMatch = isset($invoice['detail']['currency_code']) && $invoice['detail']['currency_code'] == $Parameters['CurrencyCode'];
                    }

                    // Filter: Amount range
                    if (!empty($Parameters['LowerAmount']) || !empty($Parameters['UpperAmount'])) {
                        $total = $invoice['amount']['value'] ?? 0;
                        $lower = $Parameters['LowerAmount'] ?? 0;
                        $upper = $Parameters['UpperAmount'] ?? 999999;
                        $amountMatch = ($total >= $lower && $total <= $upper);
                    }

                    // Filter: Memo (check detail.note or similar)
                    if (!empty($Parameters['Memo'])) {
                        $memo = $invoice['detail']['note'] ?? '';
                        $memoMatch = stripos($memo, $Parameters['Memo']) !== false;
                    }

                    // Must satisfy all filters
                    return $statusMatch && $emailMatch && $invoiceEmailMatch && $invoiceNumberMatch && $currencyMatch && $amountMatch && $memoMatch;
                });

                $result = [
                    'success' => true,
                    'headers' => $response['headers'],
                    'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'total_items' => count($filteredInvoices),
                    'invoices' => array_values($filteredInvoices),
                    'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                ];

                return $result;
            }

            return [
                'success' => false,
                'headers' => $response['headers'],
                'status' => !empty($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'message' => !empty($response['body']['message']) ? $response['body']['message'] : 'Unknown error',
                'full_response' => $response['body'],
                'raw_response' => !empty($response['raw_response']) ? $response['raw_response'] : [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
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
