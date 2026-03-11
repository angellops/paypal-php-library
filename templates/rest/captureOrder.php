<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id,
    	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Validate API mode
$PayPal->ValidateMode('rest');

// Get Order ID stored in session from create order response
$orderId = $_SESSION['createOrderResponse']['order_id'];

// Fetch order details from PayPal
$getOrderResponce = $PayPal->getOrder($orderId);
echo '<b>getOrder Details</b><br /><pre>';
print_r($getOrderResponce);
echo '<br /><br /></pre>';

// Capture the order payment
$captureOrderResponce = $PayPal->captureOrder($orderId);
echo '<b>captureOrder Details</b><br /><pre>';
print_r($captureOrderResponce);
echo '<br /><br /></pre>';