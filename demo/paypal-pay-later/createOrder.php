<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
}

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
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
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

/**
 * Build PayPal items array from shopping cart session
 */
$items = [];
foreach ($_SESSION['shopping_cart']['items'] as $id => $item) {
    $items[] = [
        'name' => $item['name'],
        'unit_amount' => [
            'currency_code' => 'USD',
            'value' => number_format($item['price'], 2, '.', '')
        ],
        'quantity' => (string) $item['qty']
    ];
}

$currency = 'USD';

/**
 * Amount breakdown required for Pay Later and order validation
 */
$breakdown = [
    'item_total' => [
        'currency_code' => $currency,
        'value' => number_format($_SESSION['shopping_cart']['subtotal'], 2)
    ],
    'shipping' => [
        'currency_code' => $currency,
        'value' => number_format($_SESSION['shopping_cart']['shipping'], 2)
    ],
    'handling' => [
        'currency_code' => $currency,
        'value' => number_format($_SESSION['shopping_cart']['handling'], 2)
    ],
    'tax_total' => [
        'currency_code' => $currency,
        'value' => number_format($_SESSION['shopping_cart']['tax'], 2)
    ]
];

/**
 * Create PayPal order payload
 */
$orderPayload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($_SESSION['shopping_cart']['grand_total'], 2, '.', ''),
                'breakdown' => $breakdown
            ],
            'items' => $items
        ]
    ],
    'application_context' => [
        'return_url' => $domain . 'demo/paypal-pay-later/getOrder.php',
        'cancel_url' => $domain . 'demo/paypal-pay-later/',
        'shipping_preference' => 'GET_FROM_FILE',
        'user_action' => 'PAY_NOW'
    ]
];

/**
 * Create PayPal order
 */
$PayPalResult = $PayPal->createOrder($orderPayload);

/**
 * Handle PayPal response
 */
if ( $PayPalResult['success'] ) {
    // Store order ID in session
    $_SESSION['paypal_token'] = $PayPalResult['order_id'];

    // Redirect buyer to PayPal approval page
    header('Location: ' . $PayPalResult['approval_url']);
} else {
    // Store errors and redirect to error page
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../error.php');
}