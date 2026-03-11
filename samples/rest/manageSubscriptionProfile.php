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

// Validate API mode
$PayPal->ValidateMode('rest');

// Prepare request arrays
$PayPalRequestData = array(
        'subscription_id' => 'I-4PVLBPJVE48L',                                  // Subscription ID of the profile you want to manage
        'subscription_action' => 'cancel',                                      // options: cancel | suspend | activate
        'subscription_reason' => 'Canceling the subscription profile.'          // Reason for the change in status
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->ManageSubscriptionProfile($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);