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
$remindInvoiceFields = array(
    'subject' => 'This is the subject.', 			// Subject of the cancelation notification.
	'note' => 'This is a note to the payer.' 		// Note to send the payer within the cancelation notification.
);

$PayPalRequestData = array(
    'InvoiceID' => 'INV2-2HKE-U26U-9AAF-LN6B',      // Invoice ID of the invoice
    'RemindInvoiceFields' => $remindInvoiceFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->remindInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
