<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
    'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'PayPalAPIUpgrade' => $api_upgrade,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature, 
	'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

// Prepare request arrays
$UAFields = array(
    'transactionid' => '', 				// Required. The value of the authorization's transaction identification number returned by a PayPal product.  Char length: 17 single-byte chars.
    'ipaddress' => '',					// IP address of the customer.
    'shiptoname' => '',
    'shiptostreet' => '',
    'shiptostreet2' => '',
    'shiptocity' => '',
    'shiptostate' => '',
    'shiptozip' => '',
    'shiptocountry' => '',              // Country code.
    'shiptophonenum' => '',
    'shiptostore' => '',                // Indicates if the item purchased will be shipped to a store location.
);
				
$PayPalRequestData = array('UAFields'=>$UAFields);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->UpdateAuthorization($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
