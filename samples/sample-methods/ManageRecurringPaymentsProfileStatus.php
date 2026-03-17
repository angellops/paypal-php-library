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
	'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);


if($api_mode === 'rest'){
	$PayPalRequestData = array(
			'subscription_id' => 'I-FH010XMYUTPD',
			'subscription_action' => 'cancel',                      // Action to be performed on the subscription.  Must be: cancel, suspend, activate
			'subscription_reason' => 'Canceling the subscription profile.'          // Reason for the change in status
	);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->ManageRecurringPaymentsProfileStatus($PayPalRequestData);

} else {
	// Prepare request arrays
	$MRPPSFields = array(
		'profileid' => 'I-W67GUR0BDTG6', 		// Required. Recurring payments profile ID returned from CreateRecurring...
		'action' => 'Reactivate', 			// Required. The action to be performed.  Mest be: Cancel, Suspend, Reactivate
		'note' => ''					// The reason for the change in status.  For express checkout the message will be included in email to buyers.  Can also be seen in both accounts in the status history.
	);
						
	$PayPalRequestData = array('MRPPSFields'=>$MRPPSFields);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->ManageRecurringPaymentsProfileStatus($PayPalRequestData);	
}

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
