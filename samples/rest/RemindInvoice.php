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
	'isAdaptive' => true,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$RemindInvoiceFields = array(
        'subject' => 'This is the subject.', 			// Subject of the cancelation notification.
	'note' => 'This is a note to the payer.' 		// Note to send the payer within the cancelation notification.
);

$PayPalRequestData = array(
        'InvoiceID' => 'INV2-2HKE-U26U-9AAF-LN6B',
        'RemindInvoiceFields' => $RemindInvoiceFields
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->RemindInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
