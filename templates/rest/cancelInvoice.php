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

$payload = [
	'subject' => '',		// Subject of the cancelation notification.
	'note' => '',			// Note to send the payer within the cancelation notification.
	'send_to_invoicer' => ''	// Indicates whether to send a copy of the cancelation notification to the merchant.  Values are:  true/false
];

$PayPalRequestData = [
	'InvoiceID' => '', 		// ID of the invoice.
	'PayloadData' => $payload 
];

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->cancelInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
