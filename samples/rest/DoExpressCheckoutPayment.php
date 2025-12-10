<?php
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

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$orderId = '';
if( $api_mode === 'rest' && !$api_upgrade ) {
	$orderId = isset($_SESSION['SetExpressCheckoutResult']['order_id']) ? $_SESSION['SetExpressCheckoutResult']['order_id'] : '';
} else {
	$orderId = isset($_SESSION['SetExpressCheckoutResult']['TOKEN']) ? $_SESSION['SetExpressCheckoutResult']['TOKEN'] : '';
}

$GECDResults = $PayPal->getOrder($orderId);
echo '<b>GetExpressCheckoutDetails</b><br /><pre />';
print_r($GECDResults);
echo '<br /><br />';

$PaymentResult = $PayPal->captureOrder($orderId);
echo '<b>DoExpressCheckoutPayment</b><br /><pre />';
print_r($PaymentResult);
echo '<br /><br />';