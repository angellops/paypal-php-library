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
$PayPalRequestData = array(
	'InvoiceID' => 'INV2-PBH7-AMDJ-N3HN-GXTS', 		// ID of the invoice.
	'Subject' => 'Invoice has been canceled.', 		// Subject of the cancelation notification.
	'NoteForPayer' => 'Note for Payer.', 			// Note to send the payer within the cancelation notification.
	'SendCopyToMerchant' => 'true'				// Indicates whether to send a copy of the cancelation notification to the merchant.  Values are:  true/false
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->CancelInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
