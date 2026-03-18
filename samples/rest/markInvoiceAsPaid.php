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
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Prepare request arrays
$markInvoiceAsPaidFields = array(
	'type' => 'EXTERNAL', 		                // Required.  ID of the invoice to mark paid.
	'method' => 'CASH', 				        // Method t hat can be used to mark an invoice as paid when the payer p ays offline.  Values are:  BankTransfer, Cash, Check, CreditCard, DebitCard, Other, PayPal, WireTransfer
	'note' => 'This is a test note.', 		    // Optional note associated with the payment.
	'date' => '2025-12-19'				        // Date the invoice was paid.
);

$PayPalRequestData = array(
    'InvoiceID' => 'INV2-5Q6P-2Q3E-ZKEG-YKTE',  // Invoice ID of the invoice
    'MarkInvoiceAsPaidFields' => $markInvoiceAsPaidFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->markInvoiceAsPaid($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
