<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'IPAddress' => $_SERVER['REMOTE_ADDR'],
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
$markInvoiceAsRefundedFields = array(
    'note' => 'Amount refunded to customer.',           // Short message explaining the refund
	'amount' => array(
        'currency_code' => 'USD',                       // Currency in which the refund is processed    
        'value' => '10.00',                             // Refund amount value
	),
	'method' => 'PAYPAL',                               // Payment method used to issue the refund
	'Date' => date('Y-m-d')                             // Date when the refund is marked
);

$PayPalRequestData = array(
	'InvoiceID' => 'INV2-5Q6P-2Q3E-ZKEG-YKTE',              // Invoice ID of the invoice to mark refunded which has been paid
	'MarkInvoiceAsRefundedFields' => $markInvoiceAsRefundedFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->markInvoiceAsRefunded($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
