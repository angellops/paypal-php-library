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
    private $api_upgrade;
    private $print_headers;
    private $base_url;
    private $LogResults;
    protected ?string $LogPath = null;

    public function __construct($config)
    {
        parent::__construct($config);

        $this->Sandbox = isset($config['Sandbox']) ? true : false;

        // Override base URL for REST API endpoints
        $this->base_url = $this->Sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        // Set REST-specific credentials
        $this->client_id = isset($config['ClientID']) ? $config['ClientID'] : '';
        $this->client_secret = isset($config['ClientSecret']) ? $config['ClientSecret'] : '';
        $this->merchant_id = isset($config['MerchantID']) ? $config['MerchantID'] : '';
        $this->api_upgrade = isset($config['PayPalAPIUpgrade']) ? $config['PayPalAPIUpgrade'] : FALSE;
        $this->print_headers = isset($config['PrintHeaders']) ? $config['PrintHeaders'] : false;
        $this->LogResults = isset($config['LogResults']) ? $config['LogResults'] : false;
        $this->LogPath = isset($config['LogPath']) ? $config['LogPath'] : '/logs/';

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
            'PayPal-Partner-Attribution-Id: ' . (isset($this->ButtonSource) ? $this->ButtonSource : 'AngellEYELLC_SI')
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
            'PayPal-Partner-Attribution-Id: ' . (isset($this->ButtonSource) ? $this->ButtonSource : 'AngellEYELLC_SI')
        ];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
     * Public method for external access 
     **/
    public function fetchAccessToken( $load_sdk_btn = false ){
        return $this->getAccessToken( $load_sdk_btn );
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
            'body' => json_decode($body, true),
            'raw_response' => $response
        ];
    }

    /**
     * Generate a PayPal-Auth-Assertion token.
     *
     * @access  public
     * @return  string  A dot-separated JWT string containing the header and payload.
     */
    public function paypalAuthAssertion() {
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
     * Obtain the available balance for a PayPal account.
     *
     * @access  public
     * @return  mixed[] Returns an array structure of the PayPal HTTP response params as well as parsed balance results, errors and the raw request/response.
     */
    function GetBalance($DataArray)
    {
        try {
            $response = $this->makeRequest('/v1/reporting/balances');

            if (in_array($response['status_code'], [200, 201])) {
                $body = $response['body'];

                if ($this->api_upgrade) {
                    $balances = [];
                    $flatBalances = [];

                    if (!empty($body['balances'])) {
                        foreach ($body['balances'] as $i => $bal) {
                            $amount = $bal['total_balance']['value'];
                            $currency = $bal['currency'];

                            // Add to BALANCERESULTS array (Classic-style)
                            $balances[] = [
                                'L_AMT' => $amount,
                                'L_CURRENCYCODE' => $currency,
                            ];

                            // Also add to flattened keys (L_AMT0, L_AMT1, ...)
                            $flatBalances["L_AMT{$i}"] = $amount;
                            $flatBalances["L_CURRENCYCODE{$i}"] = $currency;
                        }
                    }

                    $result = array_merge(
                        $flatBalances,
                        [
                            'TIMESTAMP'      => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                            'ACK'            => 'Success',
                            'BALANCERESULTS' => $balances,
                            'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                        ]
                    );
                    
                    // return response
                    return $result;
                }

                // Normal REST-style response
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $body,
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
    public function createOrder($orderData, $paypalRequestId = null, $includeAuth = false)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders', 'POST', $orderData, $paypalRequestId, false, $includeAuth);

            if( in_array($response['status_code'], [201, 200]) ) {
                return [
                    'success' => true,
                    'order_id' => $response['body']['id'],
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
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
    public function getOrder($orderId, $paypalRequestId = null, $includeAuth = false)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId, 'GET', [], $paypalRequestId, false, $includeAuth);

            if ($response['status_code'] === 200) {
                return [
                    'success' => true,
                    'order' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to get order details',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
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
    public function authorizeOrder($orderId, $paypalRequestId = null, $includeAuth = false)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/authorize', 'POST', [], $paypalRequestId, false, $includeAuth);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'authorization_id' => $response['body']['purchase_units'][0]['payments']['authorizations'][0]['id'],
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to authorize order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
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
    public function captureOrder($orderId, $paypalRequestId = null, $includeAuth = false)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/capture', 'POST', [], $paypalRequestId, false, $includeAuth);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'capture_id' => $response['body']['purchase_units'][0]['payments']['captures'][0]['id'],
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture order',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
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
     * Patch Order
     */
    public function patchOrder($DataArray) {
        try {
            $orderId = isset($DataArray['OrderID']) ? $DataArray['OrderID'] : '';
            $updateData = isset($DataArray['UpdateData']) ? $DataArray['UpdateData'] : [];

            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId, 'PATCH', $updateData);

            if (in_array($response['status_code'], [200, 204])) {
                return [
                    'success' => true,
                    'message' => 'Order updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to update order details',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function trackOrder($DataArray) {
        try {
            $orderId = isset($DataArray['OrderID']) ? $DataArray['OrderID'] : '';
            $trackData = isset($DataArray['TrackData']) ? $DataArray['TrackData'] : [];

            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/track', 'POST', $trackData);

            $purchase_units = $response['body']['purchase_units'][0] ? $response['body']['purchase_units'][0] : [];
            $shipping_trackers = $purchase_units['shipping']['trackers'] ? $purchase_units['shipping']['trackers'] : [];
            $tracking_ids_array = !empty($shipping_trackers) ? array_column($shipping_trackers, 'id') : [];
            $tracking_status_array = !empty($shipping_trackers) ? array_column($shipping_trackers, 'status') : [];
            $tracking_ids_string = !empty($tracking_ids_array) ? implode(', ', $tracking_ids_array) : '';
            $tracking_status_string = !empty($tracking_status_array) ? implode(', ', $tracking_status_array) : '';

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ? $response['body']['status'] : $response['status_code'],
                    'tracking_ids' => $tracking_ids_string,
                    'tracking_status' => $tracking_status_string,
                    'full_response' => $response['body']
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to track order details',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => $response['body'],
                'raw_response' => $response['raw_response']
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
     * Save log info to a location on the disk.
     *
     * @param $log_path
     * @param $filename
     * @param $data
     * @return bool
     */
    function Logger($log_path, $filename, $data) {
        if ($this->LogResults) {
            // REST log folder
            $rest_path = rtrim($log_path, '/') . '/rest/';

            // Create folder if not exists
            if (!is_dir($rest_path)) {
                mkdir($rest_path, 0755, true);
            }

            $timestamp = date('mdY_gi_s_A_');
            $file = $rest_path . $timestamp . $filename . '.txt';

            $fh = fopen($file, 'w');
            fwrite($fh, print_r($data, true));
            fclose($fh);
        }
        return true;
    }

    public function DoDirectPayment($paymentData) {
        $paymentsMappedData = [];

        $OrderItems = !empty($paymentData['OrderItems']) ? $paymentData['OrderItems'] : [];
        $PaymentAmount = !empty($paymentData['PaymentDetails']['amt']) ? $paymentData['PaymentDetails']['amt'] : 0.00;
        $PaymentSubTotal = !empty($_SESSION['shopping_cart']['subtotal']) ? number_format($_SESSION['shopping_cart']['subtotal'], 2) : 0.00;
        $PaymentShipping = !empty($paymentData['PaymentDetails']['shippingamt']) ? $paymentData['PaymentDetails']['shippingamt'] : 0.00;
        $PaymentHandling = !empty($paymentData['PaymentDetails']['handlingamt']) ? $paymentData['PaymentDetails']['handlingamt'] : 0.00;
        $PaymentTax = !empty($paymentData['PaymentDetails']['taxamt']) ? $paymentData['PaymentDetails']['taxamt'] : 0.00;
        $PaymentCurrency = !empty($paymentData['PaymentDetails']['currencycode']) ? $paymentData['PaymentDetails']['currencycode'] : 'USD';
        
        $line_items = [];
        foreach ($OrderItems as $item) {
            $line_items[] = [
                'name' => substr($item['name'], 0, 127),       
                'sku'  => substr($item['number'], 0, 127),     
                'unit_amount' => [
                    'currency_code' => $PaymentCurrency,
                    'value' => number_format($item['amt'], 2)                    
                ],
                'quantity' => (string)$item['qty'],               
                'category' => 'PHYSICAL_GOODS',
                'commodity_code' => '86101700',          
                'tax' => [
                    'currency_code' => $PaymentCurrency,
                    'value' => '0.00'
                ]
            ];
        }
        
        $paymentsMappedData['intent'] = !empty($paymentData['DPFields']['restintent']) ? $paymentData['DPFields']['restintent'] : 'CAPTURE';
        $paymentsMappedData['purchase_units'] = [
            [
                'reference_id' => 'ORDER-' . strtoupper(uniqid()),
                'invoice_id' => 'INV-' . strtoupper(uniqid()),
                'amount' => [
                    'currency_code' => $PaymentCurrency,
                    'value' => !empty($paymentData['PaymentDetails']['amt']) ? $paymentData['PaymentDetails']['amt'] : 0.00,
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => $PaymentCurrency,
                            'value' => $PaymentSubTotal
                        ],
                        'shipping' => [
                            'currency_code' => $PaymentCurrency,
                            'value' => $PaymentShipping
                        ],
                        'handling' => [
                            'currency_code' => $PaymentCurrency,
                            'value' => $PaymentHandling
                        ],
                        'tax_total' => [
                            'currency_code' => $PaymentCurrency,
                            'value' => $PaymentTax
                        ]
                    ]
                ],
                'items' => $line_items,
                'shipping' => [
                    'address' => [
                        'address_line_1' => $_SESSION['billing']['street'],
                        'admin_area_2'   => $_SESSION['billing']['city'],
                        'admin_area_1'   => $_SESSION['billing']['state'],
                        'postal_code'    => $_SESSION['billing']['zip'],
                        'country_code'   => $_SESSION['billing']['countrycode']
                    ]
                ]
            ]
        ];

        /**
         * Expiry conversion (MMYYYY → YYYY-MM)
         */
        $expiry = !empty($paymentData['CCDetails']['expdate']) ? $paymentData['CCDetails']['expdate'] : '';
        if (strlen($expiry) === 6) {
            $expiry = substr($expiry, 2, 4) . '-' . substr($expiry, 0, 2);
        }

        $paymentsMappedData['payment_source'] = [
            'card' => [
                'number' => !empty($paymentData['CCDetails']['acct']) ? $paymentData['CCDetails']['acct'] : '',
                'expiry' => $expiry,
                'security_code' => !empty($paymentData['CCDetails']['cvv2']) ? $paymentData['CCDetails']['cvv2'] : '',
                'name' => (!empty($paymentData['PayerName']['firstname']) ? $paymentData['PayerName']['firstname'] : '') . ' ' . (!empty($paymentData['PayerName']['lastname']) ? $paymentData['PayerName']['lastname'] : ''),
                'billing_address' => [
                    'address_line_1' => !empty($paymentData['BillingAddress']['street']) ? $paymentData['BillingAddress']['street'] : '',
                    'admin_area_2' => !empty($paymentData['BillingAddress']['city']) ? $paymentData['BillingAddress']['city'] : '',
                    'admin_area_1' => !empty($paymentData['BillingAddress']['state']) ? $paymentData['BillingAddress']['state'] : '',
                    'postal_code' => !empty($paymentData['BillingAddress']['zip']) ? $paymentData['BillingAddress']['zip'] : '',
                    'country_code' => !empty($paymentData['BillingAddress']['countrycode']) ? $paymentData['BillingAddress']['countrycode'] : ''
                ],
                'attributes' => [
                    'verification' => [
                        'method' => 'SCA_ALWAYS' // Forces 3DS flow presentation
                    ]
                ],
                'vault' => [
                    'store_in_vault' => 'ON_SUCCESS'
                ],
                'experience_context' => [
                    'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                    'user_action' => 'PAY_NOW'
                ]
            ]  
        ];

        $paypalRequestId = uniqid('pprid_', true);

        $response = $this->makeRequest('/v2/checkout/orders', 'POST', $paymentsMappedData, $paypalRequestId);

        $responseSimplified = [];

        // Handle Response
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {

            if( $this->api_upgrade ) {
                $responseSimplified = array(
                    'SUCCESS' => true,
                    'ACK' => 'Success',
                    'AMT' => $PaymentAmount,
                    'TRANSACTIONID' => !empty($response['body']['id']) ? $response['body']['id'] : '',
                    'CURRENCYCODE' => $PaymentCurrency,
                    'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                    'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                    'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                );

            } else {
                $responseSimplified = array(
                    'SUCCESS' => true,
                    'STATUSCODE' => !empty($response['status_code']) ? $response['status_code'] : 0,
                    'RESPONSE' => !empty($response['body']) ? $response['body'] : [],
                    'RAWRESPONSE' => !empty($response['raw_response']) ? $response['raw_response'] : [],
                );
            }
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
     * Creates a PayPal Order (REST API) equivalent to Classic SetExpressCheckout.
     *
     * Accepts Classic NVP-style array input ($DataArray), converts it
     * into a PayPal REST /v2/checkout/orders payload, sends the create
     * order request, and returns normalized NVP-compatible output.
     */
    function SetExpressCheckout($DataArray) {
        $SECFields = isset($DataArray['SECFields']) ? $DataArray['SECFields'] : [];
        $paymentsList = isset($DataArray['Payments']) ? $DataArray['Payments'] : [];
        $payerData = isset($DataArray['PayerData']) ? $DataArray['PayerData'] : [];

        $purchase_units = [];

        foreach ($paymentsList as $index => $payments) {
            $items = isset($payments['order_items']) ? $payments['order_items'] : [];
            $purchase_items = [];
            $item_total = 0;

            foreach ($items as $it) {
                $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
                $amt = isset($it['amt']) ? (float)$it['amt'] : 0;

                $purchase_items[] = [
                    "name" => isset($it['name']) ? $it['name'] : "",
                    "description" => isset($it['desc']) ? $it['desc'] : "",
                    "quantity" => (string)$qty,
                    "unit_amount" => [
                        "currency_code" => isset($payments['currencycode']) ? $payments['currencycode'] : "USD",
                        "value" => number_format($amt, 2, '.', '')
                    ],
                    "category" =>
                        isset($it['itemcategory'])
                            ? (strtolower($it['itemcategory']) === 'digital'
                                ? 'DIGITAL_GOODS'
                                : (strtolower($it['itemcategory']) === 'physical'
                                    ? 'PHYSICAL_GOODS'
                                    : $it['itemcategory']
                                )
                            )
                            : ""
                ];
                $item_total += ($amt * $qty);
            }

            $currency = isset($payments['currencycode']) ? $payments['currencycode'] : "USD";

            $amount = [
                "currency_code" => $currency,
                "value" => number_format(isset($payments['amt']) ? $payments['amt'] : $item_total, 2, '.', ''),
                "breakdown" => [
                    "item_total" => [
                        "currency_code" => $currency,
                        "value" => number_format($item_total, 2, '.', '')
                    ],
                    "shipping" => [
                        "currency_code" => $currency,
                        "value" => number_format(isset($payments['shippingamt']) ? $payments['shippingamt'] : 0, 2, '.', '')
                    ],
                    "tax_total" => [
                        "currency_code" => $currency,
                        "value" => number_format(isset($payments['taxamt']) ? $payments['taxamt'] : 0, 2, '.', '')
                    ]
                ]
            ];

            $purchase_unit = [
                "amount" => $amount,
                "description" => isset($payments['desc']) ? $payments['desc'] : "",
                "items" => $purchase_items
            ];

            if( !empty($payments['paymentrequestid']) ) {
                $purchase_unit['reference_id'] = $payments['paymentrequestid'];
            }

            if (isset($payments['sellerpaypalaccountid']) && !empty($payments['sellerpaypalaccountid'])) {
                $purchase_unit['payee'] = [
                    'merchant_id' => $payments['sellerpaypalaccountid']
                ];
            }

            $purchase_units[] = $purchase_unit;
        }

        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => $purchase_units,
            "payment_method" => [
                "payer_selected" => "PAYPAL",
            ],
            "application_context" => [
                "return_url" => isset($SECFields['returnurl']) ? $SECFields['returnurl'] : "",
                "cancel_url" => isset($SECFields['cancelurl']) ? $SECFields['cancelurl'] : "",
                "brand_name" => isset($SECFields['brandname']) ? $SECFields['brandname'] : "",
                "landing_page" => strtoupper(isset($SECFields['landingpage']) ? $SECFields['landingpage'] : "LOGIN")
            ]
        ];

        // If we are skipping details, we need to set the shipping preference and user action
        if( isset($SECFields['skipdetails']) && $SECFields['skipdetails'] ) {
            $payload['application_context']['shipping_preference'] = 'NO_SHIPPING';
            $payload['application_context']['user_action'] = 'PAY_NOW';
        }

        if( !empty($payerData) && !empty($payerData['buyeremail']) ) {
            $payload['payer'] = [
                "email_address" => $payerData['buyeremail']
            ];
        }

        $response = $this->createOrder($payload);

        if ($this->api_upgrade && isset($response['success'])) {

            $response['TOKEN'] = isset($response['order_id']) ? $response['order_id'] : '';
            $response['REDIRECTURL'] = isset($response['approval_url']) ? $response['approval_url'] : '';
            $response['ACK'] = 'Success';
            $response['TIMESTAMP'] = gmdate('Y-m-d\TH:i:s\Z');
            $response['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : [];

            // Cleanup for backward compatibility
            unset(
                $response['success'],
                $response['order_id'],
                $response['approval_url'],
                $response['raw_response']
            );
        }

        return $response;
    }

    /**
     * Retrieves PayPal Order details (REST API) equivalent to Classic
     * GetExpressCheckoutDetails. Converts REST order details into
     * Classic NVP-compatible output.
     *
     * Required structure of $DataArray:
     */
    function GetExpressCheckoutDetails($DataArray) {
        $orderId = isset($DataArray['DECPFields']['token']) ? $DataArray['DECPFields']['token'] : '';

        $response = $this->getOrder($orderId);

        if ($this->api_upgrade && isset($response['success'])) {
            $responseData = array();

            $responseData['TOKEN']  = isset($DataArray['DECPFields']['token']) ? $DataArray['DECPFields']['token'] : '';
            $responseData['BILLINGAGREEMENTACCEPTEDSTATUS']  = false;
            $responseData['NOTE']   = isset($DataArray['Payments'][0]['notetext']) ? $DataArray['Payments'][0]['notetext'] : '';
            $responseData['CHECKOUTSTATUS'] = '';
            $responseData['TIMESTAMP'] = gmdate('Y-m-d\TH:i:s\Z');
            $responseData['CORRELATIONID'] = '';
            $responseData['ACK'] = 'Success';

            $responseData['PAYERID'] = isset($response['order']['payer']['payer_id']) ? $response['order']['payer']['payer_id'] : '';
            $responseData['PAYERSTATUS'] = isset($response['order']['payment_source']['paypal']['account_status']) ? $response['order']['payment_source']['paypal']['account_status'] : '';
            $responseData['EMAIL'] = isset($response['order']['payer']['email_address']) ? $response['order']['payer']['email_address'] : '';
            $responseData['FIRSTNAME'] = isset($response['order']['payer']['name']['given_name']) ? $response['order']['payer']['name']['given_name'] : '';
            $responseData['LASTNAME'] = isset($response['order']['payer']['name']['surname']) ? $response['order']['payer']['name']['surname'] : '';
            $responseData['COUNTRYCODE'] = isset($response['order']['payer']['address']['country_code']) ? $response['order']['payer']['address']['country_code'] : '';
            
            // Additional PAYER ADDRESS DETAILS
            $purchaseUnit = $response['order']['purchase_units'][0];
            $shipping = isset($purchaseUnit['shipping']) ? $purchaseUnit['shipping'] : [];
            $responseData['SHIPPINGDATA'] = isset($shipping) ? $shipping : [];

            $responseData['DESC']   = isset($DataArray['Payments'][0]['desc']) ? $DataArray['Payments'][0]['desc'] : '';
            $responseData['CURRENCYCODE'] = isset($DataArray['Payments'][0]['currencycode']) ? $DataArray['Payments'][0]['currencycode'] : '';
            $responseData['AMT']    = isset($DataArray['Payments'][0]['amt']) ? $DataArray['Payments'][0]['amt'] : '0.00';
            $responseData['ITEMAMT'] = isset($DataArray['Payments'][0]['itemamt']) ? $DataArray['Payments'][0]['itemamt'] : '0.00';
            $responseData['SHIPPINGAMT'] = isset($DataArray['Payments'][0]['shippingamt']) ? $DataArray['Payments'][0]['shippingamt'] : '0.00';
            $responseData['TAXAMT'] = isset($DataArray['Payments'][0]['taxamt']) ? $DataArray['Payments'][0]['taxamt'] : '0.00';
            $responseData['HANDLINGAMT'] = isset($DataArray['Payments'][0]['handlingamt']) ? $DataArray['Payments'][0]['handlingamt'] : '0.00';

            // PAYMENTREQUEST LEVEL
            $responseData['PAYMENTS'] = array();
            $payments = isset($DataArray['Payments']) ? $DataArray['Payments'] : array();
            foreach ($payments as $pIndex => $payment) {
                $responseData["PAYMENTREQUEST_{$pIndex}_AMT"] = isset($payment['amt']) ? $payment['amt'] : '0.00';
                $responseData["PAYMENTREQUEST_{$pIndex}_CURRENCYCODE"] = isset($payment['currencycode']) ? $payment['currencycode'] : '';
                $responseData["PAYMENTREQUEST_{$pIndex}_ITEMAMT"] = isset($payment['itemamt']) ? $payment['itemamt'] : '0.00';
                $responseData["PAYMENTREQUEST_{$pIndex}_SHIPPINGAMT"] = isset($payment['shippingamt']) ? $payment['shippingamt'] : '0.00';
                $responseData["PAYMENTREQUEST_{$pIndex}_TAXAMT"] = isset($payment['taxamt']) ? $payment['taxamt'] : '0.00';
                $responseData["PAYMENTREQUEST_{$pIndex}_HANDLINGAMT"] = isset($payment['handlingamt']) ? $payment['handlingamt'] : '0.00';
                $responseData["PAYMENTREQUEST_{$pIndex}_DESC"] = isset($payment['desc']) ? $payment['desc'] : '';
                $responseData["PAYMENTREQUEST_{$pIndex}_NOTETEXT"] = isset($payment['notetext']) ? $payment['notetext'] : '';
                $responseData["PAYMENTREQUEST_{$pIndex}_PAYMENTACTION"] = isset($payment['paymentaction']) ? $payment['paymentaction'] : 'Sale';
                $responseData["PAYMENTREQUEST_{$pIndex}_SELLERPAYPALACCOUNTID"] = isset($payment['sellerpaypalaccountid']) ? $payment['sellerpaypalaccountid'] : '';

                $items = isset($payment['order_items']) ? $payment['order_items'] : array();
                $orderItems = array();

                foreach ($items as $i => $item) {
                    $responseData["L_NAME{$i}"] = isset($item['name']) ? $item['name'] : '';
                    $responseData["L_DESC{$i}"] = isset($item['desc']) ? $item['desc'] : '';
                    $responseData["L_NUMBER{$i}"] = isset($item['number']) ? $item['number'] : '';
                    $responseData["L_QTY{$i}"] = isset($item['qty']) ? $item['qty'] : '';
                    $responseData["L_AMT{$i}"] = isset($item['amt']) ? $item['amt'] : '';
                    $responseData["L_TAXAMT{$i}"] = isset($item['taxamt']) ? $item['taxamt'] : '0.00';

                    /* Payment-specific item mapping */
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_NAME{$i}"] = isset($item['name']) ? $item['name'] : '';
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_DESC{$i}"] = isset($item['desc']) ? $item['desc'] : '';
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_NUMBER{$i}"] = isset($item['number']) ? $item['number'] : '';
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_QTY{$i}"] = isset($item['qty']) ? $item['qty'] : '';
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_AMT{$i}"] = isset($item['amt']) ? $item['amt'] : '';
                    $responseData["L_PAYMENTREQUEST_{$pIndex}_TAXAMT{$i}"] = isset($item['taxamt']) ? $item['taxamt'] : '0.00';

                    $orderItems[] = array(
                        'NAME'   => isset($item['name']) ? $item['name'] : '',
                        'DESC'   => isset($item['desc']) ? $item['desc'] : '',
                        'NUMBER' => isset($item['number']) ? $item['number'] : '',
                        'QTY'    => isset($item['qty']) ? $item['qty'] : '',
                        'AMT'    => isset($item['amt']) ? $item['amt'] : '',
                        'TAXAMT' => isset($item['taxamt']) ? $item['taxamt'] : '0.00',
                    );
                }

                $responseData['PAYMENTS'][$pIndex] = array(
                    'AMT'          => isset($payment['amt']) ? $payment['amt'] : '0.00',
                    'CURRENCYCODE' => isset($payment['currencycode']) ? $payment['currencycode'] : '',
                    'ITEMAMT'      => isset($payment['itemamt']) ? $payment['itemamt'] : '0.00',
                    'SHIPPINGAMT'  => isset($payment['shippingamt']) ? $payment['shippingamt'] : '0.00',
                    'TAXAMT'       => isset($payment['taxamt']) ? $payment['taxamt'] : '0.00',
                    'HANDLINGAMT'  => isset($payment['handlingamt']) ? $payment['handlingamt'] : '0.00',
                    'DESC'         => isset($payment['desc']) ? $payment['desc'] : '',
                    'NOTETEXT'     => isset($payment['notetext']) ? $payment['notetext'] : '',
                    'ORDERITEMS'   => $orderItems
                );
            }

            $responseData['FULLRESPONSE'] = isset($response['order']) ? $response['order'] : array();
            $responseData['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : array();

            return $responseData;
        }

        return $response;
    }

    /**
     * Captures a PayPal Order (REST API) equivalent to Classic
     * DoExpressCheckoutPayment. Converts REST capture response
     * into Classic PayPal NVP-compatible structure.
     */
    function DoExpressCheckoutPayment($DataArray) {
        $orderId = isset($DataArray['DECPFields']['token']) ? $DataArray['DECPFields']['token'] : '';

        $response = $this->captureOrder($orderId);

        if ($this->api_upgrade && isset($response['success'])) {
            $responseData = array();

            $responseData['TOKEN'] = isset($response['capture_id']) ? $response['capture_id'] : '';
            $responseData['BILLINGAGREEMENTACCEPTEDSTATUS'] = false;
            $responseData['ACK'] = 'Success';
            $responseData['TIMESTAMP'] = gmdate('Y-m-d\TH:i:s\Z');
            $responseData['INSURANCEOPTIONSELECTED'] = 'false';
            $responseData['SHIPPINGOPTIONISDEFAULT'] = 'false';
            $responseData['ERRORS'] = array();

            $responseData['PAYMENTS'] = array();
            $purchaseUnits = isset($response['full_response']['purchase_units']) ? $response['full_response']['purchase_units'] : array();
            $paymentIndex = 0;
            foreach ($purchaseUnits as $unitIndex => $unit) {
                $captures = array();
                if (isset($unit['payments']['captures']) && is_array($unit['payments']['captures'])) {
                    $captures = $unit['payments']['captures'];
                }
                foreach ($captures as $capture) {
                    $amount = isset($capture['amount']['value']) ? $capture['amount']['value'] : '';
                    $currency = isset($capture['amount']['currency_code']) ? $capture['amount']['currency_code'] : '';
                    $fee = isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) ? $capture['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                    $gross = isset($capture['seller_receivable_breakdown']['gross_amount']['value']) ? $capture['seller_receivable_breakdown']['gross_amount']['value'] : '';
                    $net = isset($capture['seller_receivable_breakdown']['net_amount']['value']) ? $capture['seller_receivable_breakdown']['net_amount']['value'] : '';
                    $tax = '';
                    $protectionTypes = '';
                    if ( isset($capture['seller_protection']['dispute_categories']) && is_array($capture['seller_protection']['dispute_categories']) ) {
                        $protectionTypes = implode(',', $capture['seller_protection']['dispute_categories']);
                    }

                    $responseData["PAYMENTINFO_{$paymentIndex}_TRANSACTIONID"] = isset($capture['id']) ? $capture['id'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_TRANSACTIONTYPE"] = "cart";
                    $responseData["PAYMENTINFO_{$paymentIndex}_PAYMENTTYPE"] = "instant";
                    $responseData["PAYMENTINFO_{$paymentIndex}_ORDERTIME"] = isset($capture['create_time']) ? $capture['create_time'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_AMT"] = $amount;
                    $responseData["PAYMENTINFO_{$paymentIndex}_FEEAMT"] = $fee;
                    $responseData["PAYMENTINFO_{$paymentIndex}_TAXAMT"] = $tax;
                    $responseData["PAYMENTINFO_{$paymentIndex}_CURRENCYCODE"] = $currency;
                    $responseData["PAYMENTINFO_{$paymentIndex}_PAYMENTSTATUS"] = isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_PENDINGREASON"] = "None";
                    $responseData["PAYMENTINFO_{$paymentIndex}_REASONCODE"] = "None";
                    $responseData["PAYMENTINFO_{$paymentIndex}_PROTECTIONELIGIBILITY"] = isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_PROTECTIONELIGIBILITYTYPE"]  = $protectionTypes;
                    $responseData["PAYMENTINFO_{$paymentIndex}_ERRORCODE"] = 0;
                    $responseData["PAYMENTINFO_{$paymentIndex}_ACK"]       = "Success";
                    $responseData['PAYMENTS'][$paymentIndex] = array(
                        'TRANSACTIONID'   => isset($capture['id']) ? $capture['id'] : '',
                        'TRANSACTIONTYPE' => 'cart',
                        'PAYMENTTYPE'     => 'instant',
                        'ORDERTIME'       => isset($capture['create_time']) ? $capture['create_time'] : '',
                        'AMT'             => $amount,
                        'FEEAMT'          => $fee,
                        'SETTLEAMT'       => $net,
                        'TAXAMT'          => $tax,
                        'CURRENCYCODE'    => $currency,
                        'PAYMENTSTATUS'   => isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '',
                        'PENDINGREASON'   => 'None',
                        'REASONCODE'      => 'None',
                        'PROTECTIONELIGIBILITY' => isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '',
                        'ERRORCODE'       => 0,
                        'FMFILTERS'       => array(),
                        'ERRORS'          => array()
                    );

                    $paymentIndex++;
                }
            }

            $responseData['FULLRESPONSE'] = isset($response['full_response']) ? $response['full_response'] : array();
            $responseData['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : array();

            return $responseData;
        }

        return $response;
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
    public function CreateSubscriptionProfile($DataArray)
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
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response
            ];
        }

        return [
            'success' => false,
            'status' => $response['status_code'],
            'error' => isset($response['body']) ? $response['body'] : [],
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
                'id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                'response' => $response
            ];
        }

        return [
            'success' => false,
            'status' => $response['status_code'],
            'error' => isset($response['body']) ? $response['body'] : [],
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
                'subscription_id' => $response['body']['id'] ?? '',
                'status' => $response['status_code'] ?? 0,
                'response' => $response['body'] ?? [],
                'raw_response' => $response['raw_response'] ?? [],
            ];
        }

        return [
            'success' => false,
            'status' => $response['status_code'],
            'error' => $response['body'] ?? [],
            'raw_response' => $response['raw_response'] ?? [],
        ];
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

            if (in_array($response['status_code'], [200, 201])) {

                if( $this->api_upgrade ) {

                    $fullResponse = $response['body'];
                    $givenName = ! empty( $fullResponse['subscriber']['name']['given_name'] ) ? $fullResponse['subscriber']['name']['given_name'] : '';
                    $surname = ! empty( $fullResponse['subscriber']['name']['surname'] ) ? $fullResponse['subscriber']['name']['surname'] : '';
                    $phone_number = ! empty( $fullResponse['subscriber']['phone_number'] ) ? $fullResponse['subscriber']['phone_number'] : '';
                    $payer_id = ! empty( $fullResponse['subscriber']['payer_id'] ) ? $fullResponse['subscriber']['payer_id'] : '';
                    $subscriberName = !empty( $givenName ) ? $givenName . ' ' . $surname : $surname;

                    $responseData = array(
                        'TIMESTAMP'             => ! empty( $fullResponse['update_time'] ) ? $fullResponse['update_time'] : gmdate('c'),
                        'ACK'                   => !empty( $response['status_code'] ) && \in_array($response['status_code'],['200','201']) ? 'Success' : 'Failure',
                        'STATUS'                => ! empty( $fullResponse['status'] ) ? $fullResponse['status'] : '-', 
                        'PROFILEID'             => ! empty( $fullResponse['id'] ) ? $fullResponse['id'] : '-',
                        'DESC'                  => ! empty( $fullResponse['status_change_note'] ) ? $fullResponse['status_change_note'] : '',
                        'FIRSTNAME'             => $givenName,
                        'LASTNAME'              => $surname,
                        'SUBSCRIBERNAME'        => $subscriberName,
                        'PHONENUMBER'           => $phone_number,
                        'PAYERID'               => $payer_id,
                        'PROFILESTARTDATE'      => ! empty( $fullResponse['start_time'] ) ? $fullResponse['start_time'] : '',
                        'NEXTBILLINGDATE'       => ! empty( $fullResponse['billing_info']['next_billing_time'] ) ? $fullResponse['billing_info']['next_billing_time'] : '',
                        'NUMCYCLESCOMPLETED'    => ! empty( $fullResponse['billing_info']['cycle_executions'][0]['cycles_completed'] ) ? $fullResponse['billing_info']['cycle_executions'][0]['cycles_completed'] : '',
                        'NUMCYCLESREMAINING'    => ! empty( $fullResponse['billing_info']['cycle_executions'][0]['cycles_remaining'] ) ? $fullResponse['billing_info']['cycle_executions'][0]['cycles_remaining'] : '',
                        'OUTSTANDINGBALANCE'    => ! empty( $fullResponse['billing_info']['outstanding_balance']['value'] ) ? $fullResponse['billing_info']['outstanding_balance']['value'] : '',
                        'FAILEDPAYMENTCOUNT'    => ! empty( $fullResponse['billing_info']['failed_payments_count'] ) ? $fullResponse['billing_info']['failed_payments_count'] : '',
                        'LASTPAYMENTDATE'       => ! empty( $fullResponse['billing_info']['last_payment']['time'] ) ? $fullResponse['billing_info']['last_payment']['time'] : '',
                        'LASTPAYMENTAMT'        => ! empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '',
                        'SHIPTONAME'            => ! empty( $fullResponse['subscriber']['shipping_address']['name']['full_name'] ) ? $fullResponse['subscriber']['shipping_address']['name']['full_name'] : '',
                        'SHIPTOSTREET'          => ! empty( $fullResponse['subscriber']['shipping_address']['address']['address_line_1'] ) ? $fullResponse['subscriber']['shipping_address']['address']['address_line_1'] : '',
                        'SHIPTOCITY'            => ! empty( $fullResponse['subscriber']['shipping_address']['address']['admin_area_2'] ) ? $fullResponse['subscriber']['shipping_address']['address']['admin_area_2'] : '',
                        'SHIPTOSTATE'           => ! empty( $fullResponse['subscriber']['shipping_address']['address']['admin_area_1'] ) ? $fullResponse['subscriber']['shipping_address']['address']['admin_area_1'] : '',
                        'SHIPTOZIP'             => ! empty( $fullResponse['subscriber']['shipping_address']['address']['postal_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['postal_code'] : '',
                        'SHIPTOCOUNTRYCODE'     => ! empty( $fullResponse['subscriber']['shipping_address']['address']['country_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['country_code'] : '',
                        'SHIPTOCOUNTRY'         => ! empty( $fullResponse['subscriber']['shipping_address']['address']['country_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['country_code'] : '',
                        'SHIPADDRESSOWNER'      => ! empty( $fullResponse['subscriber']['tenant'] ) ? $fullResponse['subscriber']['tenant'] : '',
                        'CURRENCYCODE'          => ! empty( $fullResponse['billing_info']['last_payment']['amount']['currency_code'] ) ? $fullResponse['billing_info']['last_payment']['amount']['currency_code'] : '',
                        'AMT'                   => ! empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '', 
                        'REGULARAMT'            => ! empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '',
                        'SUBSCRIBEREMAIL'       => ! empty( $fullResponse['subscriber']['email_address'] ) ? $fullResponse['subscriber']['email_address'] : '',
                        'FULLRESPONSE'          => ! empty( $fullResponse ) ? $fullResponse : [],
                        'RAWRESPONSE'           => ! empty( $response['raw_response'] ) ? $response['raw_response'] : [],
                    );

                    return $responseData;
                }

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

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                if ($this->api_upgrade) {
                    $result = [
                        'success'        => true,
                        'status'         => $response['body']['status'] ?? $response['status_code'],
                        'ACK'            => 'Success',
                        'TIMESTAMP'      => gmdate('c'),
                        'PROFILEID'      => isset($subscriptionId) ? $subscriptionId : '',
                        'L_LONGMESSAGE0' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                        'full_response'  => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                    ];

                    return $result;
                }

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

            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {

                if($this->api_upgrade) {
                    $result = [
                        'success'        => true,
                        'status'         => $response['body']['status'] ?? $response['status_code'],
                        'TIMESTAMP'      => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                        'ACK'            => 'Success',
                        'L_LONGMESSAGE0' => 'Patch Operations completed successfully which may not return a body',
                        'full_response'  => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                        'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                    ];

                    return $result;
                }

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
            if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
                if( $this->api_upgrade ) {
                    $result = [
                        'success'        => true,
                        'TIMESTAMP'      => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                        'ACK'            => 'Success',
                        'L_LONGMESSAGE0' => isset($response['body']['details']['message']) ? $response['body']['details']['message'] : '',
                        'order'          => $response['body'],
                        'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                    ];

                    return $result;
                }
                
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

                if( $this->api_upgrade ) {
                    $result = [
                        'ACK' => 'Success',
                        'TIMESTAMP' => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                        'success' => true,
                        'L_LONGMESSAGE' => isset($body['message']) ? $body['message'] : '',
                        'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                        'full_response' => $response['body'],
                        'RAWRESPONSE' => isset($response['raw_response']) ? $response['raw_response'] : [],
                    ];

                    return $result;
                }

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
     * Create a PayPal Vault Setup Token.
     *
     * This function initiates the PayPal Vault flow by creating a setup token.
     * The setup token is used to redirect the buyer to PayPal for approval
     * and later exchanged for a payment token (vaulted payment method).
     *
     * API:
     * POST /v3/vault/setup-tokens
     */
    public function createVaultSetupToken($DataArray) {
        try {
            $payloadData = [
                'payment_source' => [
                    'paypal' => [
                        'usage_type' => 'MERCHANT',
                        'customer_type'=> 'CONSUMER',
                        'experience_context' => [
                            'brand_name' => isset($DataArray['brand_name']) ? $DataArray['brand_name'] : 'My Store',
                            'locale' => isset($DataArray['locale']) ? $DataArray['locale'] : 'en-US',
                            'shipping_preference' => 'NO_SHIPPING',
                            'return_url' => isset($DataArray['return_url']) ? $DataArray['return_url'] : '',
                            'cancel_url' => isset($DataArray['cancel_url']) ? $DataArray['cancel_url'] : '',
                        ]
                    ]
                ]
            ];

            $response = $this->makeRequest('/v3/vault/setup-tokens', 'POST', $payloadData, null, false, true);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'setup_token' => isset($response['body']['id']) ? $response['body']['id'] : '',
                    'customer_id' => isset($response['body']['customer']['id']) ? $response['body']['customer']['id'] : '',
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'approval_url' => $this->getApprovalUrl($response['body']['links']),
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create setup token',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'full_response' => $response['body'],
                'raw_response' => $response['raw_response']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve PayPal Vault Setup Token details.
     *
     * This function fetches the current status and details of a setup token.
     * Commonly used to verify whether the buyer has approved the setup token
     * before creating a payment token.
     *
     * API:
     * GET /v3/vault/setup-tokens/{setup_token}
     */
    public function getVaultSetupTokenDetails($setupToken) {
        try {
            $response = $this->makeRequest('/v3/vault/setup-tokens/' . urlencode($setupToken), 'GET', null, null, false, true);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'setup_token' => isset($response['body']['id']) ? $response['body']['id'] : '',
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get setup token',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'full_response' => $response['body'],
                'raw_response' => $response['raw_response']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a PayPal Vault Payment Token from an approved setup token.
     *
     * This function exchanges an APPROVED setup token for a payment token.
     * The payment token represents a vaulted PayPal payment method that can
     * be reused for future payments without buyer interaction.
     *
     * API:
     * POST /v3/vault/payment-tokens
     */
    public function createVaultPaymentToken($DataArray) {
        try {
            $vaultPaymentData = ( !empty($DataArray) && $DataArray['vault_payment_data'] ) ? $DataArray['vault_payment_data'] : [];
            $paypalRequestId = uniqid('pprid_', true);

            $response = $this->makeRequest('/v3/vault/payment-tokens', 'POST', $vaultPaymentData, $paypalRequestId, false, true);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'vault_token' => isset($response['body']['id']) ? $response['body']['id'] : '',
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create setup token',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'full_response' => $response['body'],
                'raw_response' => $response['raw_response']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a PayPal merchant onboarding (partner referral).
     *
     * This method initiates the PayPal Partner Referrals API call
     * to onboard a new merchant under the partner account.
     */
    public function createMerchantOnboarding($DataArray) {
        try {
            $response = $this->makeRequest('/v2/customer/partner-referrals', 'POST', $DataArray);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create setup token',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'full_response' => $response['body'],
                'raw_response' => $response['raw_response']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify merchant onboarding status with PayPal.
     *
     * This method checks whether a merchant has successfully completed
     * the onboarding flow and granted the requested permissions/scopes.
     */
    public function verifyMerchantOnboarding($merchantId) {
        try {
            $response = $this->makeRequest('/v1/customer/partners/' . $this->merchant_id . '/merchant-integrations/' . $merchantId);

            if (in_array($response['status_code'], [200, 201])) {
                return [
                    'success' => true,
                    'status' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                    'full_response' => $response['body'],
                    'raw_response' => $response['raw_response']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create setup token',
                'status_code' => isset($response['body']['status']) ? $response['body']['status'] : $response['status_code'],
                'full_response' => $response['body'],
                'raw_response' => $response['raw_response']
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

            if (in_array($response['status_code'], [200, 201])) {

                if ($this->api_upgrade) {
                    $result = [
                        'PAL' => isset($response['body']['payer_id']) ? $response['body']['payer_id'] : '',
                        'TIMESTAMP' => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                        'ACK' => 'Success',
                        'full_response' => $response['body'],
                        'RAWRESPONSE' => isset($response['raw_response']) ? $response['raw_response'] : [],
                    ];

                    return $result;
                }

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
     * Creates a sample recurring payments profile with predefined data.
     *
     * This method sets up a sample product, billing plan, and subscription
     * for demonstration purposes. It uses hardcoded values for the product name,
     * plan details, and subscriber information.
     */
    public function CreateRecurringPaymentsProfile( $domain ){

        $ProductData = array(
            "name" => "Angell EYE Web Hosting",
            "description" => "Web hosting recurring subscription",
            "type" => "SERVICE",
            "category" => "SOFTWARE",
        );

        $PlanData = array(
                "product_id" => '',
                "name" => "Daily Hosting Plan",
                "description" => "Daily recurring billing for hosting",
                "billing_cycles" => array(
                        array(
                                "frequency" => array(
                                        "interval_unit" => "DAY",
                                        "interval_count" => 1
                                ),
                                "tenure_type" => "REGULAR",
                                "sequence" => 1,
                                "total_cycles" => 0, // 0 = infinite
                                "pricing_scheme" => array(
                                        "fixed_price" => array(
                                                "value" => "10.00",
                                                "currency_code" => "USD"
                                        )
                                )
                        )
                ),
                "payment_preferences" => array(
                        "auto_bill_outstanding" => true,
                        "setup_fee_failure_action" => "CONTINUE",
                        "payment_failure_threshold" => 3
                )
        );

        $SubscriptionData = array(
                "plan_id" => '',
                "start_time" => gmdate("Y-m-d\TH:i:s\Z", strtotime("+10 minutes")),
                "subscriber" => array(
                        "name" => array(
                                "given_name" => "Tester",
                                "surname" => "Testerson"
                        ),
                        "email_address" => "tester@hey.com",
                        "shipping_address" => array(
                                "name" => array("full_name" => "Tester Testerson"),
                                "address" => array(
                                        "address_line_1" => "123 Test Ave.",
                                        "admin_area_2" => "Grandview",
                                        "admin_area_1" => "MO",
                                        "postal_code" => "64030",
                                        "country_code" => "US"
                                )
                        )
                ),
                "application_context" => array(
                        "brand_name" => "Angell EYE Web Hosting",
                        "locale" => "en-US",
                        "shipping_preference" => "SET_PROVIDED_ADDRESS",
                        "user_action" => "SUBSCRIBE_NOW",
                        'return_url' => $domain . 'samples/rest/GetRecurringPaymentsProfileDetails.php',
                        'cancel_url' => $domain . 'samples/rest/', 
                )
        );

        $PayPalRequestData = array(
            'ProductData' => $ProductData, 
            'PlanData' => $PlanData, 
            'SubscriptionData' => $SubscriptionData, 
        );

        return $this->CreateSubscriptionProfile($PayPalRequestData);
    }

    /**
     * Retrieves details of a recurring payments profile using the provided request data.
     *
     * This method serves as a wrapper for the `GetSubscriptionProfile` function,
     * allowing users to fetch information about a specific recurring payments profile
     * based on the input parameters.
     */
    public function GetRecurringPaymentsProfileDetails( $PayPalRequestData ){
        return $this->GetSubscriptionProfile($PayPalRequestData);
    }

    /**
     * Manage recurring payments profile status by delegating to ManageSubscriptionProfile.
     *
     * This function is a wrapper that calls the ManageSubscriptionProfile method
     * to perform actions such as canceling, suspending, or activating a subscription.
     */
    public function ManageRecurringPaymentsProfileStatus($DataArray) {
        return $this->ManageSubscriptionProfile($DataArray);
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
     * Sends a reminder for a specific invoice using the PayPal REST API.
     *
     * This method triggers a reminder email to the recipient of the specified invoice.
     * It makes a POST request to the `/v2/invoicing/invoices/{invoice_id}/remind` endpoint.
     */
    public function RemindInvoice($InvoiceData)
    {
        try {
            $payload = !empty($InvoiceData['RemindInvoiceFields']) ? $InvoiceData['RemindInvoiceFields'] : [];
            $InvoiceID = isset($InvoiceData['InvoiceID']) ? $InvoiceData['InvoiceID'] : '';

            $response = $this->makeRequest('/v2/invoicing/invoices/' . $InvoiceID . '/remind', 'POST', $payload, null, true);

            if ($response['status_code'] === 201) {
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice reminder sent successfully which may not return a body']
                ];
            }

            return [
                'success' => false,
                'error' => '',
                'status_code' => $response['body']['status'] ?? $response['status_code'],
                'details' => !empty($response['body']) ? $response['body'] : ['message' => 'Invoice reminder sent successfully which may not return a body']
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
    public function SearchInvoices($InvoiceData)
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

            if (in_array($response['status_code'], [200, 201])) {
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
                    'status_code' => $response['status_code'],
                    'total_items' => count($filteredInvoices),
                    'invoices' => array_values($filteredInvoices),
                ];

                return $result;
            }

            return [
                'success' => false,
                'status_code' => $response['status_code'],
                'message' => $response['body']['message'] ?? 'Unknown error',
                'full_response' => $response['body']
            ];
        } catch (Exception $e) {
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