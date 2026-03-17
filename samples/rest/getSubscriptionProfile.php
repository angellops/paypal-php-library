<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
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

$subscriptionID = isset($_GET['subscription_id']) ? $_GET['subscription_id'] : 'I-HS7W8E8BN4WG';

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->getSubscriptionProfile($subscriptionID);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);