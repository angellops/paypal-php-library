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
        'PrintHeaders' => $print_headers,
        'LogResults' => $log_results,
        'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Vault token ID
$VaultTokenID = '';

/**
 * Create PayPal order payload
 */
$orderPayload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
                [
                        'amount' => [
                                'currency_code' => '',
                                'value' => '',
                        ],
                        'shipping' => [
                                'name' => [
                                        'full_name' => ''
                                ],
                                'address' => [
                                        'address_line_1' => '',
                                        'admin_area_2' => '',
                                        'admin_area_1' => '',
                                        'postal_code' => '',
                                        'country_code' => ''
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