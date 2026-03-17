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

	$PayPalResult = $PayPal->RequestPermissions();

	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';

} else {

	// Prepare request arrays
	$Scope = array(
		'EXPRESS_CHECKOUT', 
		'DIRECT_PAYMENT', 
	/*	'SETTLEMENT_CONSOLIDATION', 
		'SETTLEMENT_REPORTING', 
		'AUTH_CAPTURE', 
		'MOBILE_CHECKOUT', 
	*/	'BILLING_AGREEMENT', 
		'REFERENCE_TRANSACTION', 
	/*	'AIR_TRAVEL', 
		'MASS_PAY', 
	*/	'TRANSACTION_DETAILS',
		'TRANSACTION_SEARCH',
		'RECURRING_PAYMENTS',
		'ACCOUNT_BALANCE',
		'ENCRYPTED_WEBSITE_PAYMENTS',
		'REFUND',
		'NON_REFERENCED_CREDIT',
		'BUTTON_MANAGER',
		'MANAGE_PENDING_TRANSACTION_STATUS',
		'RECURRING_PAYMENT_REPORT',
		'EXTENDED_PRO_PROCESSING_REPORT',
		'EXCEPTION_PROCESSING_REPORT',
		'ACCOUNT_MANAGEMENT_PERMISSION',
		'ACCESS_BASIC_PERSONAL_DATA',
		'ACCESS_ADVANCED_PERSONAL_DATA'
	);

	$RequestPermissionsFields = array(
		'Scope' => $Scope, 						// Required.   
		'Callback' => ''						// Required.  Your callback function that specifies actions to take after the account holder grants or denies the request.
	);

	$PayPalRequestData = array('RequestPermissionsFields' => $RequestPermissionsFields);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->Adaptive->RequestPermissions($PayPalRequestData);

	// Write the contents of the response array to the screen for demo purposes.
	echo '<pre />';
	echo "<p><strong>Deprecated Notice:</strong> The classic RequestPermissions method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";

}

print_r($PayPalResult);
