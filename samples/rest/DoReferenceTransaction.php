<?php
require_once('../../includes/config.php');
require_once('../../autoload.php');

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$orderId = isset($_GET['token']) ? $_GET['token'] : '';

$PaymentResult = $PayPal->authorizeOrder($orderId);

$authorizationId = isset($PaymentResult['authorization_id']) ? $PaymentResult['authorization_id'] : '';

$PayPalResult = $PayPal->captureAutorizedOrder($authorizationId);

echo '<pre>';
print_r($PayPalResult);