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
    	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

if ( $api_mode === 'classic' ) {
	/**
	 * Now we setup the parameters for the reference transaction.
	 * This is where we use the transaction ID or billing agreement ID
	 * from a previous Express Checkout (or Payments Pro) transaction.
	 */
	$ReferenceID = 'B-20L17406RW402900E';

	$DRTFields = array(
	'referenceid' => $ReferenceID, 						// Required.  A transaction ID from a previous purchase, such as a credit card charage using DoDirectPayment, or a billing agreement ID
	'paymentaction' => 'Sale', 						// How you want to obtain payment.  Values are:  Authorization, Sale
	'ipaddress' => $_SERVER['REMOTE_ADDR'], 							// IP address of the buyer's browser
	);

	/**
	 * Here we'll setup the payment details for the reference transaction
	 * that we are processing.
	 */
	$PaymentDetails = array(
		'amt' => '10.00', 							// Required. Total amount of the order, including shipping, handling, and tax.
		'currencycode' => 'USD', 					// A three-character currency code.  Default is USD.
	);

	/**
	 * Now we gather all of the arrays above into a single array.
	 */
	$PayPalRequestData = array(
		'DRTFields' => $DRTFields,
		'PaymentDetails' => $PaymentDetails,
	);

	/**
	 * Here we are making the call to the DoExpressCheckoutPayment function in the library,
	 * and we're passing in our $PayPalRequestData that we just set above.
	 */
	$PayPalResult = $PayPal->DoReferenceTransaction($PayPalRequestData);
} elseif ( $api_mode === 'rest' ) {
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
} else {
	$PayPalResult = [
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