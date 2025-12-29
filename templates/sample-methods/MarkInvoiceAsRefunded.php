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

if( $api_mode === 'rest' ) {
	// Prepare request arrays
	$MarkInvoiceAsRefundedFields = array(
		'note' => '',
		'amount' => array(
					'currency_code' => '',
					'value' => ''
		),
		'method' => '',
		'Date' => ''
	);

	$PayPalRequestData = array(
		'InvoiceID' => '',              // Invoice ID of the invoice to mark refunded which has been paid
		'MarkInvoiceAsRefundedFields' => $MarkInvoiceAsRefundedFields
	);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->MarkInvoiceAsRefunded($PayPalRequestData);
	echo '<pre />';
} else {
	// Prepare request arrays
	$MarkInvoiceAsRefundedFields = array(
		'InvoiceID' => '', 			// Required.  ID of the invoice to mark paid.
		'Note' => '', 		// Optional note associated with the payment.
		'Date' => ''						// Date the invoice was paid.
	);

	$PayPalRequestData = array('MarkInvoiceAsRefundedFields' => $MarkInvoiceAsRefundedFields);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->Adaptive->MarkInvoiceAsRefunded($PayPalRequestData);
	echo '<pre />';
	echo "<p><strong>Deprecated Notice:</strong> The classic MarkInvoiceAsRefunded method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
}

// Write the contents of the response array to the screen for demo purposes.
print_r($PayPalResult);
