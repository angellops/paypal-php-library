<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature, 
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$PayPalRequestedData = array(
	'paymentID' => isset($_GET['paymentId']) ? $_GET['paymentId'] : '',
	'token' => isset($_GET['PayerID']) ? $_GET['PayerID'] : '',
	'payerID' => isset($_GET['PayerID']) ? $_GET['PayerID'] : '',
);

$ExecuteResult = $PayPal->ExecutePayment($PayPalRequestedData);

echo '<pre>';
print_r($ExecuteResult);