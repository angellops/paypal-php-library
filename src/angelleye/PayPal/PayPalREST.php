<?php

namespace angelleye\PayPal;

/**
 * PayPal REST API Class
 * Extends the main PayPal class for consistency and shared functionality
 */
class PayPalREST extends PayPal {
    
    // REST-specific properties
    private $accessToken;
    private $tokenExpiry;
    private $client_id;
    private $client_secret;
    private $base_url;
    
    public function __construct($config) {
        // Call parent constructor first
        parent::__construct($config);
        
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
    public function test() {
        return [
            'success' => true,
            'message' => 'PayPalREST class initialized successfully',
            'base_url' => $this->base_url,  // Now matches the property
            'mode' => $this->Sandbox  // Now this exists
        ];
    }
    
    /**
     * Get OAuth 2.0 access token
     * Caches token for 9 hours to avoid redundant API calls
     */
    private function getAccessToken() {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }
        
        $auth = base64_encode($this->client_id . ':' . $this->client_secret);
        
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
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
        
        throw new Exception('Failed to get OAuth token: ' . $response);
    }

    /**
     * Make authenticated REST API request
     */
    protected function makeRequest($endpoint, $method = 'GET', $data = null) {
        $token = $this->getAccessToken();
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
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
     * Test OAuth authentication
     */
    public function testOAuth() {
        try {
            $token = $this->getAccessToken();
            return [
                'success' => true,
                'message' => 'OAuth token retrieved successfully',
                'token_preview' => substr($token, 0, 20) . '...',
                'expires_in' => $this->tokenExpiry - time()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OAuth failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test simple API call (get webhook list - minimal permissions needed)
     */
    public function testAPICall() {
        try {
            $response = $this->makeRequest('/v1/notifications/webhooks');
            return [
                'success' => $response['status_code'] >= 200 && $response['status_code'] < 300,
                'message' => $response['status_code'] >= 200 && $response['status_code'] < 300 
                    ? 'API call successful' 
                    : 'API call failed',
                'status_code' => $response['status_code']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'API call failed: ' . $e->getMessage(),
                'status_code' => 0
            ];
        }
    }
    
    // REST-specific methods will go here
    // OAuth, HTTP requests, etc.
}