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
    
    // REST-specific methods will go here
    // OAuth, HTTP requests, etc.
}