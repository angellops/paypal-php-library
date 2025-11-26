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

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$InvoiceID = 'INV2-AB7V-NL6F-MW2A-HRUT';

if( $api_mode === 'rest' ) {
	$PayPalResult = $PayPal->GetInvoiceDetails($InvoiceID);

	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';
} else {
	$PayPalResult = $PayPal->Adaptive->GetInvoiceDetails($InvoiceID);

	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';
	echo "<p><strong>Deprecated Notice:</strong> The classic GetInvoiceDetails method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
}

print_r($PayPalResult);
