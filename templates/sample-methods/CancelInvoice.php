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
	'PayPalAPIUpgrade' => $api_upgrade,
    'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret

);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$CancelInvoiceFields = array(
	'InvoiceID' => '', 		// ID of the invoice.
	'Subject' => '', 		// Subject of the cancelation notification.
	'NoteForPayer' => '', 		// Note to send the payer within the cancelation notification.
	'SendCopyToMerchant' => ''	// Indicates whether to send a copy of the cancelation notification to the merchant.  Values are:  true/false
);

if( $api_mode === 'rest' ) {
	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->CancelInvoice($CancelInvoiceFields);
	
	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';

} else {
	$PayPalRequestData = array('CancelInvoiceFields' => $CancelInvoiceFields);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->Adaptive->CancelInvoice($PayPalRequestData);

	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';
	echo "<p><strong>Deprecated Notice:</strong> The classic CancelInvoice method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
}

print_r($PayPalResult);
