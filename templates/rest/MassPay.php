<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
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

// Prepare request arrays
$SenderBatchHeaders = array(
	'sender_batch_id' => "",        // A unique identifier of the batch payout. Must be unique for each payout.
	"email_subject"   => "",        // The subject line of the email that PayPal sends when the transaction is completed. Same for all recipients.  255 char max.
        "email_message"   => "",        // The message that PayPal sends to the recipients of the payout email. 255 char max.
);

// Typically, you'll loop through some sort of records to build your MPItems array. 
// Here I simply include 3 items individually.  

$Item1 = array(
	"recipient_type" => "",         // The type of recipient. Must be EMAIL or PHONE.
        "amount" => array(
                "value" => "",          // The amount to be paid to the recipient.
                "currency" => ""        // The currency code.
        ),
        "receiver" => "",               // The recipient's email address.
        "note" => "",                   // A note to the recipient.
        "sender_item_id" => ""          // A unique identifier for the item in the payout batch.
);
			
$Item2 = array(
	"recipient_type" => "",         // The type of recipient. Must be EMAIL or PHONE.
        "amount" => array(
                "value" => "",          // The amount to be paid to the recipient.
                "currency" => ""        // The currency code.
        ),
        "receiver" => "",               // The recipient's email address.
        "note" => "",                   // A note to the recipient.
        "sender_item_id" => ""          // A unique identifier for the item in the payout batch.
);
			
$Item3 = array(
	"recipient_type" => "",         // The type of recipient. Must be EMAIL or PHONE.
        "amount" => array(
                "value" => "",          // The amount to be paid to the recipient.
                "currency" => ""        // The currency code.
        ),
        "receiver" => "",               // The recipient's email address.
        "note" => "",                   // A note to the recipient.
        "sender_item_id" => ""          // A unique identifier for the item in the payout batch.
);
									
$Items = array($Item1, $Item2, $Item3);  // etc

$PayPalRequestData = array('sender_batch_header'=>$SenderBatchHeaders, 'items' => $Items);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->MassPay($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
