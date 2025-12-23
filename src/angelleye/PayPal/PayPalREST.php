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
    private $api_upgrade;
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
        $this->api_upgrade = isset($config['PayPalAPIUpgrade']) ? $config['PayPalAPIUpgrade'] : FALSE;
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
    private function getHeaders($includeAuth = true, $contentType = 'application/json', $requestId = null, $isInvoiceRequest = false)
    {;
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

                    // call logger
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);
                    
                    // return response
                    return $result;
                }
                
                // call logger
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                // Normal REST-style response
                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $body,
                ];
            }

            // call logger
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

        // Log Request and Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $payload);
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

        // Log Request and Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $payload);
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $responseSimplified);

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

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $responseSimplified);

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
    public function getOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId, 'GET');

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
    public function authorizeOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/authorize', 'POST');

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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
    public function captureOrder($orderId)
    {
        try {
            $response = $this->makeRequest('/v2/checkout/orders/' . $orderId . '/capture', 'POST');

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

        $PaymentAmount = !empty($paymentData['PaymentDetails']['amt']) ? $paymentData['PaymentDetails']['amt'] : 0.00;
        $PaymentCurrency = !empty($paymentData['PaymentDetails']['currencycode']) ? $paymentData['PaymentDetails']['currencycode'] : '';
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

            if( $this->api_upgrade ) {
                $responseSimplified = array(
                    'SUCCESS' => true,
                    'ACK' => 'Success',
                    'AMT' => $PaymentAmount,
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

        // Log Request and Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $paymentsMappedData);
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $responseSimplified);

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
        $payments = isset($DataArray['Payments'][0]) ? $DataArray['Payments'][0] : [];
        $items = isset($payments['order_items']) ? $payments['order_items'] : [];

        $purchase_items = [];
        $item_total = 0;

        foreach ($items as $it) {
            $purchase_items[] = array(
                "name" => isset($it["name"]) ? $it["name"] : "",
                "description" => isset($it["desc"]) ? $it["desc"] : "",
                "quantity" => isset($it["qty"]) ? $it["qty"] : "1",
                "unit_amount" => [
                    "currency_code" => isset($payments["currencycode"]) ? $payments["currencycode"] : "USD",
                    "value" => isset($it["amt"]) ? $it["amt"] : "0.00"
                ],
                "category" => !empty($it["itemcategory"]) && strtolower($it["itemcategory"]) === "digital"
                    ? "DIGITAL_GOODS"
                    : "PHYSICAL_GOODS"
            );

            $item_total += (float) (isset($it["amt"]) ? $it["amt"] : 0) * (int) (isset($it["qty"]) ? $it["qty"] : 1);
        }

        // Amount breakdown
        $currency = isset($payments["currencycode"]) ? $payments["currencycode"] : "USD";

        $amount = array(
            "currency_code" => $currency,
            "value" => isset($payments["amt"]) ? $payments["amt"] : "0.00",
            "breakdown" => array(
                "item_total" => array(
                    "currency_code" => $currency,
                    "value" => number_format($item_total, 2, '.', '')
                ),
                "shipping" => array(
                    "currency_code" => $currency,
                    "value" => isset($payments["shippingamt"]) ? $payments["shippingamt"] : "0.00"
                ),
                "tax_total" => array(
                    "currency_code" => $currency,
                    "value" => isset($payments["taxamt"]) ? $payments["taxamt"] : "0.00"
                )
            )
        );

        $payload = array(
            "intent" => "CAPTURE",
            "purchase_units" => array(
                array(
                    "amount" => $amount,
                    "description" => isset($payments["desc"]) ? $payments["desc"] : "",
                    "items" => $purchase_items
                )
            ),
            "application_context" => array(
                "return_url" => isset($SECFields["returnurl"]) ? $SECFields["returnurl"] : "",
                "cancel_url" => isset($SECFields["cancelurl"]) ? $SECFields["cancelurl"] : "",
                "brand_name" => isset($SECFields["brandname"]) ? $SECFields["brandname"] : "",
                "landing_page" => strtoupper(isset($SECFields["landingpage"]) ? $SECFields["landingpage"] : "LOGIN"),
            )
        );

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

        // Log Request and Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $payload);
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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
            $payment = $DataArray['Payments'][0];

            $responseData['PAYMENTREQUEST_0_AMT']          = isset($payment['amt']) ? $payment['amt'] : '0.00';
            $responseData['PAYMENTREQUEST_0_CURRENCYCODE'] = isset($payment['currencycode']) ? $payment['currencycode'] : '';
            $responseData['PAYMENTREQUEST_0_ITEMAMT']      = isset($payment['itemamt']) ? $payment['itemamt'] : '0.00';
            $responseData['PAYMENTREQUEST_0_SHIPPINGAMT']  = isset($payment['shippingamt']) ? $payment['shippingamt'] : '0.00';
            $responseData['PAYMENTREQUEST_0_TAXAMT']       = isset($payment['taxamt']) ? $payment['taxamt'] : '0.00';
            $responseData['PAYMENTREQUEST_0_HANDLINGAMT']  = isset($payment['handlingamt']) ? $payment['handlingamt'] : '0.00';
            $responseData['PAYMENTREQUEST_0_DESC']         = isset($payment['desc']) ? $payment['desc'] : '';
            $responseData['PAYMENTREQUEST_0_NOTETEXT']     = isset($payment['notetext']) ? $payment['notetext'] : '';

            // ORDER ITEMS
            $items = isset($payment['order_items']) ? $payment['order_items'] : array();
            $orderItemsArray = array();

            foreach ($items as $i => $item) {

                $responseData["L_NAME$i"]    = isset($item['name']) ? $item['name'] : '';
                $responseData["L_DESC$i"]    = isset($item['desc']) ? $item['desc'] : '';
                $responseData["L_NUMBER$i"]  = isset($item['number']) ? $item['number'] : '';
                $responseData["L_QTY$i"]     = isset($item['qty']) ? $item['qty'] : '';
                $responseData["L_AMT$i"]     = isset($item['amt']) ? $item['amt'] : '';
                $responseData["L_TAXAMT$i"]  = isset($item['taxamt']) ? $item['taxamt'] : "0.00";

                $responseData["L_PAYMENTREQUEST_0_NAME$i"]   = isset($item['name']) ? $item['name'] : '';
                $responseData["L_PAYMENTREQUEST_0_DESC$i"]   = isset($item['desc']) ? $item['desc'] : '';
                $responseData["L_PAYMENTREQUEST_0_NUMBER$i"] = isset($item['number']) ? $item['number'] : '';
                $responseData["L_PAYMENTREQUEST_0_QTY$i"]    = isset($item['qty']) ? $item['qty'] : '';
                $responseData["L_PAYMENTREQUEST_0_AMT$i"]    = isset($item['amt']) ? $item['amt'] : '';
                $responseData["L_PAYMENTREQUEST_0_TAXAMT$i"] = isset($item['taxamt']) ? $item['taxamt'] : "0.00";

                $orderItemsArray[$i] = array(
                    'L_NAME'   => isset($item['name']) ? $item['name'] : '',
                    'L_DESC'   => isset($item['desc']) ? $item['desc'] : '',
                    'L_NUMBER' => isset($item['number']) ? $item['number'] : '',
                    'L_QTY'    => isset($item['qty']) ? $item['qty'] : '',
                    'L_AMT'    => isset($item['amt']) ? $item['amt'] : '',
                    'L_OPTIONSNAME' => isset($item['optionsname']) ? $item['optionsname'] : '',
                    'L_OPTIONSVALUE' => isset($item['optionsvalue']) ? $item['optionsvalue'] : '',
                    'L_ITEMWEIGHTVALUE' => isset($item['itemweightvalue']) ? $item['itemweightvalue'] : '',
                    'L_ITEMWEIGHTUNIT'  => isset($item['itemweightunit']) ? $item['itemweightunit'] : '',
                    'L_ITEMWIDTHVALUE' => isset($item['itemwidthvalue']) ? $item['itemwidthvalue'] : '',
                    'L_ITEMWIDTHUNIT'  => isset($item['itemwidthunit']) ? $item['itemwidthunit'] : '',
                    'L_ITEMHEIGHTVALUE' => isset($item['itemheightvalue']) ? $item['itemheightvalue'] : '',
                    'L_ITEMHEIGHTUNIT'  => isset($item['itemheightunit']) ? $item['itemheightunit'] : '',
                    'L_ITEMLENGTHVALUE' => isset($item['itemlengthvalue']) ? $item['itemlengthvalue'] : '',
                    'L_ITEMLENGTHUNIT'  => isset($item['itemlengthunit']) ? $item['itemlengthunit'] : '',
                    'L_EBAYITEMNUMBER' => isset($item['ebayitemnumber']) ? $item['ebayitemnumber'] : '',
                    'L_EBAYITEMAUCTIONTXNID' => isset($item['ebayitemauctiontxnid']) ? $item['ebayitemauctiontxnid'] : '',
                    'L_EBAYITEMORDERID' => isset($item['ebayitemorderid']) ? $item['ebayitemorderid'] : '',
                    'L_EBAYITEMCARTID' => isset($item['ebayitemcartid']) ? $item['ebayitemcartid'] : '',
                    'L_TAXAMT' => isset($item['taxamt']) ? $item['taxamt'] : "0.00",
                );
            }

            // PAYMENT REQUEST BLOCK FOR RESPONSE
            $responseData['PAYMENTS'] = array();
            $responseData['PAYMENTS'][0] = array(
                'AMT'          => isset($payment['amt']) ? $payment['amt'] : '0.00',
                'CURRENCYCODE' => isset($payment['currencycode']) ? $payment['currencycode'] : '',
                'ITEMAMT'      => isset($payment['itemamt']) ? $payment['itemamt'] : '0.00',
                'SHIPPINGAMT'  => isset($payment['shippingamt']) ? $payment['shippingamt'] : '0.00',
                'TAXAMT'       => isset($payment['taxamt']) ? $payment['taxamt'] : '0.00',
                'HANDLINGAMT'  => isset($payment['handlingamt']) ? $payment['handlingamt'] : '0.00',
                'DESC'         => isset($payment['desc']) ? $payment['desc'] : '',
                'NOTETEXT'     => isset($payment['notetext']) ? $payment['notetext'] : '',
                'ORDERITEMS'   => array_map(
                    function ($it) {
                        return array(
                            'NAME'  => isset($it['L_NAME']) ? $it['L_NAME'] : '',
                            'DESC'  => isset($it['L_DESC']) ? $it['L_DESC'] : '',
                            'AMT'   => isset($it['L_AMT']) ? $it['L_AMT'] : '',
                            'NUMBER'=> isset($it['L_NUMBER']) ? $it['L_NUMBER'] : '',
                            'QTY'   => isset($it['L_QTY']) ? $it['L_QTY'] : '',
                            'TAXAMT'=> isset($it['L_TAXAMT']) ? $it['L_TAXAMT'] : '0.00',

                            'OPTIONSNAME' => isset($it['L_OPTIONSNAME']) ? $it['L_OPTIONSNAME'] : '',
                            'OPTIONSVALUE' => isset($it['L_OPTIONSVALUE']) ? $it['L_OPTIONSVALUE'] : '',

                            'ITEMWEIGHTVALUE' => isset($it['L_ITEMWEIGHTVALUE']) ? $it['L_ITEMWEIGHTVALUE'] : '',
                            'ITEMWEIGHTUNIT'  => isset($it['L_ITEMWEIGHTUNIT']) ? $it['L_ITEMWEIGHTUNIT'] : '',

                            'ITEMWIDTHVALUE' => isset($it['L_ITEMWIDTHVALUE']) ? $it['L_ITEMWIDTHVALUE'] : '',
                            'ITEMWIDTHUNIT'  => isset($it['L_ITEMWIDTHUNIT']) ? $it['L_ITEMWIDTHUNIT'] : '',

                            'ITEMHEIGHTVALUE' => isset($it['L_ITEMHEIGHTVALUE']) ? $it['L_ITEMHEIGHTVALUE'] : '',
                            'ITEMHEIGHTUNIT'  => isset($it['L_ITEMHEIGHTUNIT']) ? $it['L_ITEMHEIGHTUNIT'] : '',

                            'ITEMLENGTHVALUE' => isset($it['L_ITEMLENGTHVALUE']) ? $it['L_ITEMLENGTHVALUE'] : '',
                            'ITEMLENGTHUNIT'  => isset($it['L_ITEMLENGTHUNIT']) ? $it['L_ITEMLENGTHUNIT'] : '',

                            'EBAYITEMNUMBER' => isset($it['L_EBAYITEMNUMBER']) ? $it['L_EBAYITEMNUMBER'] : '',
                            'EBAYITEMAUCTIONTXNID' => isset($it['L_EBAYITEMAUCTIONTXNID']) ? $it['L_EBAYITEMAUCTIONTXNID'] : '',
                            'EBAYITEMORDERID' => isset($it['L_EBAYITEMORDERID']) ? $it['L_EBAYITEMORDERID'] : '',
                            'EBAYITEMCARTID' => isset($it['L_EBAYITEMCARTID']) ? $it['L_EBAYITEMCARTID'] : '',
                        );
                    },
                    $orderItemsArray
                )
            );

            $responseData['FULLRESPONSE'] = isset($response['order']) ? $response['order'] : array();
            $responseData['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : array();
        }

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', ($this->api_upgrade ? $responseData : $response));

        return $this->api_upgrade ? $responseData : $response;
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

            $fullResponse  = isset($response['full_response']) ? $response['full_response'] : array();
            $payer = isset($fullResponse['payer']) ? $fullResponse['payer'] : array();

            $unit  = isset($fullResponse['purchase_units'][0]) ? $fullResponse['purchase_units'][0] : array();

            $capture = array();
            if (isset($unit['payments']['captures'][0])) {
                $capture = $unit['payments']['captures'][0];
            }

            // Amounts
            $amount   = isset($capture['amount']['value']) ? $capture['amount']['value'] : '';
            $currency = isset($capture['amount']['currency_code']) ? $capture['amount']['currency_code'] : '';

            $fee = isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) 
                    ? $capture['seller_receivable_breakdown']['paypal_fee']['value'] 
                    : '';

            $gross = isset($capture['seller_receivable_breakdown']['gross_amount']['value']) 
                    ? $capture['seller_receivable_breakdown']['gross_amount']['value'] 
                    : '';

            $net = isset($capture['seller_receivable_breakdown']['net_amount']['value']) 
                ? $capture['seller_receivable_breakdown']['net_amount']['value'] 
                : '';

            // Tax not provided in REST
            $tax = '';

            // Protection categories
            $protectionTypes = '';
            if (isset($capture['seller_protection']['dispute_categories']) 
                && is_array($capture['seller_protection']['dispute_categories'])) {

                $protectionTypes = implode(',', $capture['seller_protection']['dispute_categories']);
            }

            // Build NVP output
            $responseData = array(
                "TOKEN"                                 => isset($response['capture_id']) ? $response['capture_id'] : '',
                "BILLINGAGREEMENTACCEPTEDSTATUS"        => false,
                "NOTE"                                  => isset($DataArray['Payments'][0]['notetext']) ? $DataArray['Payments'][0]['notetext'] : '',
                "CHECKOUTSTATUS"                        => '',
                "TIMESTAMP"                             => gmdate('Y-m-d\TH:i:s\Z'),
                "CORRELATIONID"                         => '',
                "ACK"                                   => 'Success',
                "INSURANCEOPTIONSELECTED"               => 'false',
                "SHIPPINGOPTIONISDEFAULT"               => 'false',
                "PAYMENTINFO_0_TRANSACTIONID"           => isset($capture['id']) ? $capture['id'] : '',
                "PAYMENTINFO_0_TRANSACTIONTYPE"         => "cart",
                "PAYMENTINFO_0_PAYMENTTYPE"             => "instant",
                "PAYMENTINFO_0_ORDERTIME"               => isset($capture['create_time']) ? $capture['create_time'] : '',
                "PAYMENTINFO_0_AMT"                     => $amount,
                "PAYMENTINFO_0_FEEAMT"                  => $fee,
                "PAYMENTINFO_0_TAXAMT"                  => $tax,
                "PAYMENTINFO_0_CURRENCYCODE"            => $currency,
                "PAYMENTINFO_0_PAYMENTSTATUS"           => isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '',
                "PAYMENTINFO_0_PENDINGREASON"           => "None",
                "PAYMENTINFO_0_REASONCODE"              => "None",
                "PAYMENTINFO_0_PROTECTIONELIGIBILITY"   => isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '',
                "PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE" => $protectionTypes,
                "PAYMENTINFO_0_SELLERPAYPALACCOUNTID"   => "",
                "PAYMENTINFO_0_SECUREMERCHANTACCOUNTID" => "",
                "PAYMENTINFO_0_ERRORCODE"               => 0,
                "PAYMENTINFO_0_ACK"                     => "Success",
                "ERRORS"                                => array(),
                "PAYMENTS"                              => array(
                    array(
                        "TRANSACTIONID"      => isset($capture['id']) ? $capture['id'] : '',
                        "TRANSACTIONTYPE"    => "cart",
                        "PAYMENTTYPE"        => "instant",
                        "ORDERTIME"          => isset($capture['create_time']) ? $capture['create_time'] : '',
                        "AMT"                => $amount,
                        "FEEAMT"             => $fee,
                        "SETTLEAMT"          => "",
                        "TAXAMT"             => $tax,
                        "EXCHANGERATE"       => "",
                        "CURRENCYCODE"       => $currency,
                        "PAYMENTSTATUS"      => isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '',
                        "PENDINGREASON"      => "None",
                        "REASONCODE"         => "None",
                        "PROTECTIONELIGIBILITY" => isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '',
                        "ERRORCODE"          => 0,
                        "FMFILTERS"          => array(),
                        "ERRORS"             => array()
                    )
                )
            );

            $responseData['FULLRESPONSE'] = isset($response['full_response']) ? $response['full_response'] : array();
            $responseData['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : array();
        }

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', ($this->api_upgrade ? $responseData : $response));

        return $this->api_upgrade ? $responseData : $response;
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

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $DataArray);
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $responseSimplified);

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

            if (in_array($response['status_code'], [200, 201])) {

                if( $this->api_upgrade ) {

                    $fullResponse = $response['body'];
                    if( !empty( $fullResponse['subscriptions'] ) ){
                        foreach( $fullResponse['subscriptions'] as $key => $value ){
                            $subscriptionRes = $this->makeRequest('/v1/billing/subscriptions/' . $value['id'], 'GET');

                            $giventName = ! empty( $subscriptionRes['body']['subscriber']['name']['given_name'] ) ? $subscriptionRes['body']['subscriber']['name']['given_name'] : '';
                            $surname = ! empty( $subscriptionRes['body']['subscriber']['name']['surname'] ) ? $subscriptionRes['body']['subscriber']['name']['surname'] : '';
                            $subscriberName = !empty( $giventName ) ? $giventName . ' ' . $surname : $surname;

                            $responseData = array(
                                'TIMESTAMP'             => ! empty( $subscriptionRes['body']['update_time'] ) ? $subscriptionRes['body']['update_time'] : gmdate('c'),
                                'ACK'                   => !empty( $subscriptionRes['status_code'] ) && \in_array($subscriptionRes['status_code'],['200','201']) ? 'Success' : 'Failure',
                                'STATUS'                => ! empty( $subscriptionRes['body']['status'] ) ? $subscriptionRes['body']['status'] : '-', 
                                'PROFILEID'             => ! empty( $subscriptionRes['body']['id'] ) ? $subscriptionRes['body']['id'] : '-',
                                'DESC'                  => ! empty( $subscriptionRes['body']['status_change_note'] ) ? $subscriptionRes['body']['status_change_note'] : '',
                                'SUBSCRIBERNAME'        => $subscriberName,
                                'PROFILESTARTDATE'      => ! empty( $subscriptionRes['body']['start_time'] ) ? $subscriptionRes['body']['start_time'] : '',
                                'NEXTBILLINGDATE'       => ! empty( $subscriptionRes['body']['billing_info']['next_billing_time'] ) ? $subscriptionRes['body']['billing_info']['next_billing_time'] : '',
                                'NUMCYCLESCOMPLETED'    => ! empty( $subscriptionRes['body']['billing_info']['cycle_executions'][0]['cycles_completed'] ) ? $subscriptionRes['body']['billing_info']['cycle_executions'][0]['cycles_completed'] : '',
                                'NUMCYCLESREMAINING'    => ! empty( $subscriptionRes['body']['billing_info']['cycle_executions'][0]['cycles_remaining'] ) ? $subscriptionRes['body']['billing_info']['cycle_executions'][0]['cycles_remaining'] : '',
                                'OUTSTANDINGBALANCE'    => ! empty( $subscriptionRes['body']['billing_info']['outstanding_balance']['value'] ) ? $subscriptionRes['body']['billing_info']['outstanding_balance']['value'] : '',
                                'FAILEDPAYMENTCOUNT'    => ! empty( $subscriptionRes['body']['billing_info']['failed_payments_count'] ) ? $subscriptionRes['body']['billing_info']['failed_payments_count'] : '',
                                'LASTPAYMENTDATE'       => ! empty( $subscriptionRes['body']['billing_info']['last_payment']['time'] ) ? $subscriptionRes['body']['billing_info']['last_payment']['time'] : '',
                                'LASTPAYMENTAMT'        => ! empty( $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] ) ? $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] : '',
                                'SHIPTONAME'            => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['name']['full_name'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['name']['full_name'] : '',
                                'SHIPTOSTREET'          => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['address_line_1'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['address_line_1'] : '',
                                'SHIPTOCITY'            => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['admin_area_2'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['admin_area_2'] : '',
                                'SHIPTOSTATE'           => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['admin_area_1'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['admin_area_1'] : '',
                                'SHIPTOZIP'             => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['postal_code'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['postal_code'] : '',
                                'SHIPTOCOUNTRYCODE'     => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['country_code'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['country_code'] : '',
                                'SHIPTOCOUNTRY'         => ! empty( $subscriptionRes['body']['subscriber']['shipping_address']['address']['country_code'] ) ? $subscriptionRes['body']['subscriber']['shipping_address']['address']['country_code'] : '',
                                'SHIPADDRESSOWNER'      => ! empty( $subscriptionRes['body']['subscriber']['tenant'] ) ? $subscriptionRes['body']['subscriber']['tenant'] : '',
                                'CURRENCYCODE'          => ! empty( $subscriptionRes['body']['billing_info']['last_payment']['amount']['currency_code'] ) ? $subscriptionRes['body']['billing_info']['last_payment']['amount']['currency_code'] : '',
                                'AMT'                   => ! empty( $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] ) ? $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] : '', 
                                'REGULARAMT'            => ! empty( $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] ) ? $subscriptionRes['body']['billing_info']['last_payment']['amount']['value'] : '',
                                'SUBSCRIBEREMAIL'       => ! empty( $subscriptionRes['body']['subscriber']['email_address'] ) ? $subscriptionRes['body']['subscriber']['email_address'] : '',
                                'FULLRESPONSE'          => ! empty( $subscriptionRes['body'] ) ? $subscriptionRes['body'] : [],
                                'RAWRESPONSE'           => ! empty( $subscriptionRes['raw_response'] ) ? $subscriptionRes['raw_response'] : [],
                            );

                            $fullResponse['subscriptions'][$key] = $responseData;
                        }
                    }

                    $result = [
                        'success'         => true,
                        'subscription_id' => isset($response['body']['id']) ? $response['body']['id'] : '',
                        'status'          => $response['body']['status'] ?? $response['status_code'],
                        'full_response'   => $fullResponse
                    ];

                    // call logger
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

                    // call logger
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

                    // call logger
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

                    // call logger
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }
                
                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'order' => $response['body']
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

                    // Log Response
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            if (in_array($response['status_code'], [200, 201])) {

                if ($this->api_upgrade) {
                    $result = [
                        'PAL' => isset($response['body']['payer_id']) ? $response['body']['payer_id'] : '',
                        'TIMESTAMP' => isset($body['as_of_time']) ? $body['as_of_time'] : gmdate('c'),
                        'ACK' => 'Success',
                        'full_response' => $response['body'],
                        'RAWRESPONSE' => isset($response['raw_response']) ? $response['raw_response'] : [],
                    ];

                    // Log Response
                    $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                    return $result;
                }

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

                return [
                    'success' => true,
                    'status' => $response['body']['status'] ?? $response['status_code'],
                    'full_response' => $response['body']
                ];
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

        // Log Response
        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $responseSimplified);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Request and Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Request', $payload);
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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

                // Log Response
                $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);

                return $result;
            }

            // Log Response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $response);

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