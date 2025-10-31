<?php
if(!session_id()) session_start();

require_once('../../includes/config.php');
require_once('../../autoload.php');

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'APIVersion' => '97.0', 
	'APISubject' => '',
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$orderData = [
	'intent' => 'CAPTURE', // or 'AUTHORIZE' if you plan to capture later
   	'purchase_units' => [
		[
			'reference_id' => 'ORDER-' . uniqid(),
			'amount' => [
				'currency_code' => 'USD',
				'value' => '50.00',
				'breakdown' => [
					'item_total' => [
						'currency_code' => 'USD',
						'value' => '50.00'
					]
				]
			],
			'items' => [
				[
					'name' => 'Sample Product',
					'unit_amount' => [
						'currency_code' => 'USD',
						'value' => '25.00'
					],
					'quantity' => '2'
				]
			]
		]
    	],
	'application_context' => [
		'brand_name' => 'Angell EYE',
		'landing_page' => 'LOGIN', // or 'BILLING' for guest checkout
		'user_action' => 'CONTINUE',
		'return_url' => $domain . 'samples/rest/DoExpressCheckoutPayment.php',
		'cancel_url' => $domain . 'samples/rest/', 
	]
];

$PaymentResult = $PayPal->createOrder($orderData);

if ($PaymentResult['success']) {
	$_SESSION['paypal_order_id'] = $PaymentResult['order_id'];

	echo '<a href="' . $PaymentResult['approval_url'] . '">Click here to continue.</a><br /><br />';
	
	echo '<pre />';
	print_r($PaymentResult);
} else {
    	echo 'Error creating order: ' . $PaymentResult['error'];
}
