<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
        'Sandbox' => $sandbox,
        'PayPalAPIMode' => $api_mode,
        'PayPalAPIUpgrade' => $api_upgrade,
        'ClientID' => $rest_client_id,
        'ClientSecret' => $rest_client_secret,
        'MerchantID' => $rest_merchant_id,
        'PrintHeaders' => $print_headers,
        'LogResults' => $log_results,
        'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Vault token ID
$VaultTokenID = '1bm89546xv216635v';

/**
 * Create PayPal order payload
 */
$orderPayload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
                [
                        'amount' => [
                                'currency_code' => 'USD',
                                'value' => '10.00',
                        ],
                        'shipping' => [
                                'name' => [
                                        'full_name' => 'John Doe'
                                ],
                                'address' => [
                                        'address_line_1' => '1 Main St',
                                        'admin_area_2' => 'San Jose',
                                        'admin_area_1' => 'CA',
                                        'postal_code' => '95131',
                                        'country_code' => 'US'
                                ]
                        ]
                ]
        ],
        'payment_source' => [
                'paypal' => [
                        'vault_id' => $VaultTokenID
                ]
        ]
];

/**
 * Generate unique PayPal request ID
 */
$paypalRequestId = uniqid('pprid_', true);

/**
 * Create PayPal order
 */
$PayPalResult = $PayPal->createOrder($orderPayload, $paypalRequestId);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);