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

// Prepare request arrays
$RefundFields = [
	'value' => '',                          // Amount to refund.  Must not exceed the amount of the original payment.
        'currency_code' => '',        		// Required.  Must specify code used for original payment.  You do not need to specify if you use a payKey to refund a completed transaction.
];

$PayPalRequestData = [
	'transaction_id' => '',                 // Original PayPal transaction ID to refund
        'refund_type' => '',                    // 'full' or 'partial' refund
	'refund_fields' => [
                'amount' => $RefundFields,      // Required only for partial refunds
                'note_to_payer' => ''           // Optional note shown to the payer
        ],
];

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->refundPayments($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);