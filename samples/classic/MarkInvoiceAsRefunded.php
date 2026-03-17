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
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'APISubject' => $api_subject,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
	'isAdaptive' => true,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$MarkInvoiceAsRefundedFields = array(
	'InvoiceID' => 'INV2-GZWT-JZHS-FR3G-EDWA', 			// Required.  ID of the invoice to mark paid.
	'Note' => 'This was refunded in person via cash.', 		// Optional note associated with the payment.
	'Date' => date('Y-m-d')						// Date the invoice was paid.
);

$PayPalRequestData = array('MarkInvoiceAsRefundedFields' => $MarkInvoiceAsRefundedFields);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->Adaptive->MarkInvoiceAsRefunded($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
