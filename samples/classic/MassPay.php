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
$MPFields = array(
	'emailsubject' => 'Test MassPay', 						// The subject line of the email that PayPal sends when the transaction is completed.  Same for all recipients.  255 char max.
	'currencycode' => 'USD', 						// Three-letter currency code.
	'receivertype' => 'EmailAddress' 						// Indicates how you identify the recipients of payments in this call to MassPay.  Must be EmailAddress or UserID
);

// Typically, you'll loop through some sort of records to build your MPItems array. 
// Here I simply include 3 items individually.  

$Item1 = array(
	'l_email' => 'andrew_1342623385_per@angelleye.com', 		// Required.  Email address of recipient.  You must specify either L_EMAIL or L_RECEIVERID but you must not mix the two.
	'l_receiverid' => '', 						// Required.  ReceiverID of recipient.  Must specify this or email address, but not both.
	'l_amt' => '10.00', 						// Required.  Payment amount.
	'l_uniqueid' => 'item_1',					// Transaction-specific ID number for tracking in an accounting system.
	'l_note' => 'Thanks for your work!' 				// Custom note for each recipient.
);
			
$Item2 = array(
	'l_email' => 'usb_1329725429_biz@angelleye.com', 		// Required.  Email address of recipient.  You must specify either L_EMAIL or L_RECEIVERID but you must not mix the two.
	'l_receiverid' => '', 						// Required.  ReceiverID of recipient.  Must specify this or email address, but not both.
	'l_amt' => '10.00', 						// Required.  Payment amount.
	'l_uniqueid' => 'item_2', 					// Transaction-specific ID number for tracking in an accounting system.
	'l_note' => 'Payment for services' 				// Custom note for each recipient.
);
			
$Item3 = array(
	'l_email' => 'andrew_1277258815_per@angelleye.com', 		// Required.  Email address of recipient.  You must specify either L_EMAIL or L_RECEIVERID but you must not mix the two.
	'l_receiverid' => '', 						// Required.  ReceiverID of recipient.  Must specify this or email address, but not both.
	'l_amt' => '10.00', 						// Required.  Payment amount.
	'l_uniqueid' => 'item_3', 					// Transaction-specific ID number for tracking in an accounting system.
	'l_note' => 'Thank you!' 					// Custom note for each recipient.
);
									
$MPItems = array($Item1, $Item2, $Item3);  // etc

$PayPalRequestData = array('MPFields'=>$MPFields, 'MPItems' => $MPItems);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->MassPay($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
