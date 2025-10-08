<?php

namespace PayPal;

class PayPalREST extends PayPal {
    protected $accessToken;
    protected $tokenExpiry;
    protected $baseUrl;

    /**
     * Constructor - Initialize REST-specific settings while maintaining parent functionality
     */
    public function __construct($config = array()) {
        parent::__construct($config);
        
        // Set REST specific base URL
        $this->baseUrl = $this->mode === 'sandbox' 
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Get OAuth 2.0 access token
     */
    protected function getAccessToken() {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        $url = $this->baseUrl . '/v1/oauth2/token';
        $credentials = base64_encode(
            $this->config['rest']['client_id'] . ':' . 
            $this->config['rest']['client_secret']
        );

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded',
            'PayPal-Request-Id: ' . $this->generateRequestId()
        ];

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            curl_close($ch);
            $responseData = json_decode($response, true);

            if ($httpCode >= 400) {
                throw new \Exception(
                    "OAuth Error: " . ($responseData['error_description'] ?? 'Unknown error')
                );
            }

            $this->accessToken = $responseData['access_token'];
            $this->tokenExpiry = time() + $responseData['expires_in'] - 60;
            return $this->accessToken;

        } catch (\Exception $e) {
            $this->log->error('OAuth Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Make REST API request with OAuth
     */
    protected function makeRestRequest($endpoint, $method, $data = null, $additionalHeaders = []) {
        $headers = array_merge([
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json',
            'PayPal-Request-Id: ' . $this->generateRequestId()
        ], $additionalHeaders);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            curl_close($ch);
            $responseData = json_decode($response, true);

            if ($httpCode >= 400) {
                $this->handleRestError($responseData);
            }

            return $responseData;

        } catch (\Exception $e) {
            $this->log->error('REST API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle REST API errors
     */
    protected function handleRestError($response) {
        $message = isset($response['message']) ? $response['message'] : 'Unknown error';
        $debug_id = isset($response['debug_id']) ? $response['debug_id'] : '';
        
        $this->log->error("PayPal REST Error: $message (Debug ID: $debug_id)");
        throw new \Exception("PayPal REST Error: $message (Debug ID: $debug_id)");
    }

    /**
     * Generate unique request ID
     */
    protected function generateRequestId() {
        return vsprintf('%s%s-%s-4000-8000-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }

    /**
     * SetExpressCheckout implementation using REST API
     */
    public function SetExpressCheckout($params) {
        // Map classic params to REST format
        $orderData = [
            'intent' => isset($params['PAYMENTREQUEST_0_PAYMENTACTION']) && 
                       $params['PAYMENTREQUEST_0_PAYMENTACTION'] === 'Authorization' 
                       ? 'AUTHORIZE' : 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $params['PAYMENTREQUEST_0_CURRENCYCODE'] ?? 'USD',
                        'value' => $params['PAYMENTREQUEST_0_AMT'] ?? '0.00'
                    ]
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'return_url' => $params['RETURNURL'],
                        'cancel_url' => $params['CANCELURL'],
                        'brand_name' => $params['BRANDNAME'] ?? null,
                        'locale' => $params['LOCALECODE'] ?? 'en-US',
                        'shipping_preference' => $this->mapShippingPreference($params)
                    ]
                ]
            ]
        ];

        // Add items if present
        if (isset($params['L_PAYMENTREQUEST_0_NAME0'])) {
            $orderData['purchase_units'][0]['items'] = $this->mapLineItems($params);
            $orderData['purchase_units'][0]['amount']['breakdown'] = $this->mapAmountBreakdown($params);
        }

        // Add billing agreement details if present
        if (isset($params['L_BILLINGTYPE0'])) {
            $orderData = $this->addBillingAgreementDetails($orderData, $params);
        }

        $response = $this->makeRestRequest('/v2/checkout/orders', 'POST', $orderData);

        // Return in classic format
        return $this->formatOrderResponse($response);
    }

    /**
     * Map shipping preference from classic to REST
     */
    protected function mapShippingPreference($params) {
        if (isset($params['NOSHIPPING']) && $params['NOSHIPPING'] === '1') {
            return 'NO_SHIPPING';
        }
        if (isset($params['ADDROVERRIDE']) && $params['ADDROVERRIDE'] === '1') {
            return 'SET_PROVIDED_ADDRESS';
        }
        return 'GET_FROM_FILE';
    }

    /**
     * Map line items from classic to REST
     */
    protected function mapLineItems($params) {
        $items = [];
        $i = 0;
        while (isset($params["L_PAYMENTREQUEST_0_NAME$i"])) {
            $items[] = [
                'name' => $params["L_PAYMENTREQUEST_0_NAME$i"],
                'quantity' => $params["L_PAYMENTREQUEST_0_QTY$i"] ?? 1,
                'unit_amount' => [
                    'currency_code' => $params['PAYMENTREQUEST_0_CURRENCYCODE'] ?? 'USD',
                    'value' => $params["L_PAYMENTREQUEST_0_AMT$i"]
                ],
                'description' => $params["L_PAYMENTREQUEST_0_DESC$i"] ?? null,
                'sku' => $params["L_PAYMENTREQUEST_0_NUMBER$i"] ?? null
            ];
            $i++;
        }
        return $items;
    }

    /**
     * Format REST response to match classic API
     */
    protected function formatOrderResponse($response) {
        $result = [
            'TOKEN' => $response['id'],
            'TIMESTAMP' => date('Y-m-d\TH:i:s\Z'),
            'ACK' => 'Success'
        ];

        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $result['REDIRECTURL'] = $link['href'];
                break;
            }
        }

        return $result;
    }

    // Additional methods would follow similar pattern...
}