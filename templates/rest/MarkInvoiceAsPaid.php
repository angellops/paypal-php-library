<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'DeveloperAccountEmail' => $developer_account_email,
	'ApplicationID' => $application_id,
	'DeviceID' => $device_id,
	'IPAddress' => $_SERVER['REMOTE_ADDR'],
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
        'ClientSecret' => $rest_client_secret,
	'APISubject' => $api_subject,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$MarkInvoiceAsPaidFields = array(
	'type' => '', 		        // Required.  ID of the invoice to mark paid.
	'method' => '', 		// Method t hat can be used to mark an invoice as paid when the payer p ays offline.  Values are:  BankTransfer, Cash, Check, CreditCard, DebitCard, Other, PayPal, WireTransfer
	'note' => '', 		        // Optional note associated with the payment.
	'date' => ''			// Date the invoice was paid.
);

$PayPalRequestData = array(
        'InvoiceID' => '',
        'MarkInvoiceAsPaidFields' => $MarkInvoiceAsPaidFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->MarkInvoiceAsPaid($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
