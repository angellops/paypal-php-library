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
    'ClientSecret' => $rest_client_secret
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);


if( $api_mode === 'classic' ) {
    // Prepare request arrays
    $GRPPDFields = array(
        'profileid' => 'I-W67GUR0BDTG6'			// Profile ID of the profile you want to get details for.
    );
                    
    $PayPalRequestData = array('GRPPDFields'=>$GRPPDFields);
} else{
    $PayPalRequestData = array(
	    'token' => isset($_GET['token']) ? $_GET['token'] : '',
        'ba_token' => isset($_GET['ba_token']) ? $_GET['ba_token'] : '',
        'subscription_id' => isset($_GET['subscription_id']) ? $_GET['subscription_id'] : '',
    );
}

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->GetRecurringPaymentsProfileDetails($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
