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
		$MarkInvoiceAsPaidFields = array(
			'type' => '', 		                // Required.  ID of the invoice to mark paid.
			'method' => '', 				// Method t hat can be used to mark an invoice as paid when the payer p ays offline.  Values are:  BankTransfer, Cash, Check, CreditCard, DebitCard, Other, PayPal, WireTransfer
			'note' => '', 		// Optional note associated with the payment.
			'date' => '',
			'InvoiceID' => '',
		);

		$PayPalRequestData = array('MarkInvoiceAsPaidFields' => $MarkInvoiceAsPaidFields);
		$PayPalResult = $PayPal->MarkInvoiceAsPaid($PayPalRequestData);	
		echo '<pre />';	
} else {
	// Prepare request arrays
	$MarkInvoiceAsPaidFields = array(
		'InvoiceID' => '', 		// Required.  ID of the invoice to mark paid.
		'Method' => '', 					// Method t hat can be used to mark an invoice as paid when the payer p ays offline.  Values are:  BankTransfer, Cash, Check, CreditCard, DebitCard, Other, PayPal, WireTransfer
		'Note' => '', 			// Optional note associated with the payment.
		'Date' => ''					// Date the invoice was paid.
	);

	$PayPalRequestData = array('MarkInvoiceAsPaidFields' => $MarkInvoiceAsPaidFields);
	$PayPalResult = $PayPal->Adaptive->MarkInvoiceAsPaid($PayPalRequestData);
	echo '<pre />';
	echo "<p><strong>Deprecated Notice:</strong> The classic MarkInvoiceAsPaid method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
}

// Write the contents of the response array to the screen for demo purposes.
print_r($PayPalResult);




