<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Build PayPal order data from session
$orderData = [
	'intent' => 'CAPTURE',
    	'purchase_units' => [
		[
			'amount' => [
				'currency_code' => 'USD',
				'value' => $_SESSION['shopping_cart']['grand_total'],
				'breakdown' => [
					'item_total' => [
						'currency_code' => 'USD',
						'value' => number_format($_SESSION['shopping_cart']['subtotal'], 2, '.', '')
					],
					'shipping' => [
						'currency_code' => 'USD',
						'value' => number_format($_SESSION['shopping_cart']['shipping'], 2, '.', '')
					],
					'handling' => [
						'currency_code' => 'USD',
						'value' => number_format($_SESSION['shopping_cart']['handling'], 2, '.', '')
					],
					'tax_total' => [
						'currency_code' => 'USD',
						'value' => number_format($_SESSION['shopping_cart']['tax'], 2, '.', '')
					],
				],
			],
			'items' => array_map(function($item) {
				return [
					'name' => $item['name'],
					'sku' => $item['id'],
					'quantity' => (string)$item['qty'],
					'unit_amount' => [
						'currency_code' => 'USD',
						'value' => number_format($item['price'], 2, '.', '')
					]
				];
			}, $_SESSION['shopping_cart']['items']),
		],
    	],
	'application_context' => [
		'brand_name' => 'Angell EYE',
		'landing_page' => 'BILLING', // or 'LOGIN'
		'user_action' => 'CONTINUE',
		'return_url' => $domain . 'demo/rest/rest-checkout-line-items-v2/GetExpressCheckoutDetails.php',
		'cancel_url' => $domain . 'demo/rest/rest-checkout-line-items-v2/', 
	],
];

$PaymentResult = $PayPal->createOrder($orderData);

if ($PaymentResult['success']) {
    // Redirect to PayPal approval URL
    header('Location: ' . $PaymentResult['approval_url']);
    exit;
} else {
    // Handle error
    echo '<pre>';
    print_r($PaymentResult['error']);
    echo '</pre>';
}