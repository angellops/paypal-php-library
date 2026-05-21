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
$markInvoiceAsPaidFields = array(
	'type' => '', 		        // Required.  ID of the invoice to mark paid.
	'method' => '',            	// Method t hat can be used to mark an invoice as paid when the payer p ays offline.  Values are:  BankTransfer, Cash, Check, CreditCard, DebitCard, Other, PayPal, WireTransfer
	'note' => '', 		        // Optional note associated with the payment.
	'date' => ''			// Date the invoice was paid.
);

$PayPalRequestData = array(
    'InvoiceID' => '',			// Invoice ID of the invoice
    'MarkInvoiceAsPaidFields' => $markInvoiceAsPaidFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->markInvoiceAsPaid($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
