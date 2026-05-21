<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
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
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Vault token ID
$VaultTokenID = '1bm89546xv216635v';		// Use Stored Vault Token here

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

if ( !$PayPalResult['success'] ) {
	$errors = [
		'success' => false,
		'error' => 'Something went wrong while processing the PayPal payment.'
	];
}

/**
 * At this point, since the reference transaction is happening
 * to process a payment automatically, you'll want to handle
 * the result however works best for you.
 *
 * For example, you may be running this within a cron job.
 * In that scenario, you wouldn't be displaying anything
 * to the screen, but you might want to email yourself
 * a notification, update your database, etc.
 *
 * If this is happening within a user experience, then
 * you may want to redirect to a receipt page of some
 * sort.
 *
 * For demo purposes, we are simply dumping out the result
 * of the call so you can see what data you have to work
 * with in the response.
 */
echo '<pre />';
print_r($PayPalResult);