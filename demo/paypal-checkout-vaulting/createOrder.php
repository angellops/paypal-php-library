<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
    'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id_2,
	'ClientSecret' => $rest_client_secret_2,
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

/**
 * Create PayPal order payload
 */
$orderPayload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'amount' => [
                'currency_code' => 'USD',
                'value' => number_format($_SESSION['shopping_cart']['grand_total'], 2),
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
    'customer' => [
        'id' => $_SESSION['paypal_customer_id']
    ],
    'payment_source' => [
        'paypal' => [
            'vault_id' => $_SESSION['paypal_vault_token'],
            'stored_credential' => [
                'payment_initiator' => 'MERCHANT',
                'payment_type' => 'RECURRING',
                'usage' => 'SUBSEQUENT'
            ]
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
$PayPalResult = $PayPal->createOrder($orderPayload, $paypalRequestId, true);

/**
 * Handle PayPal response
 */
if ( $PayPalResult['success'] ) {
    // Store order ID in session
    $_SESSION['paypal_token'] = $PayPalResult['order_id'];

    // Redirect to review page
    header('Location: getCapturedOrder.php');
} else {
    // Store errors and redirect to error page
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../error.php');
}