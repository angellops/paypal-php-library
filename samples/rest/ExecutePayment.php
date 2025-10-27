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
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
	'isAdaptive' => true,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$PayPalRequestedData = array(
	'paymentID' => 'PAYID-ND7VMCY2TJ817399J8265422',	// Test Payment ID
	'token' => 'EC-4BC82606XT4947400',			// Test Token
	'PayerID' => 'QSXYL9LZ8NEYC',				// Test Payer ID
);

$ExecuteResult = $PayPal->ExecutePayment($PayPalRequestedData);

echo '<pre>';
print_r($ExecuteResult);