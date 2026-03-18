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
$RTFields = array(
    'transactionid' => '7GC28773HG079532V', 		// Required.  PayPal transaction ID for the order you're refunding.
    'payerid' => '', 								// Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
    'invoiceid' => '', 								// Your own invoice tracking number.
    'refundtype' => 'Partial', 						// Required.  Type of refund.  Must be Full, Partial, or Other.
    'amt' => '10.00', 								// Refund Amt.  Required if refund type is Partial.
    'currencycode' => 'USD', 						// Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
    'note' => 'Refund Issued!',  					// Custom memo about the refund.  255 char max.
    'retryuntil' => '', 							// Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
    'refundsource' => '', 							// Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
    'merchantstoredetail' => '', 					// Information about the merchant store.
    'refundadvice' => '', 							// Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
    'refunditemdetails' => '', 						// Details about the individual items to be returned.
    'msgsubid' => '', 								// A message ID used for idempotence to uniquely identify a message.
    'storeid' => '', 								// ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
    'terminalid' => '',								// ID of the terminal.  50 char max.
    'shippingamt' => '',                            // The amount of shipping refunded.
    'taxamt' => '',                                 // The amount of tax refunded.
);

// You may include up to 16 $MerchantDataVar arrays within the $MerchantDataVars array.
$MerchantDataVars = array();
$MerchantDataVar = array(
    'key' => 'Key1',                                // The key name of a merchant data key-value pair passed with the transaction.
    'value' => 'Value1',                            // The value of the data passed for the key.
);
array_push($MerchantDataVars, $MerchantDataVar);

$MerchantDataVar = array(
    'key' => 'Key2',                                // The key name of a merchant data key-value pair passed with the transaction.
    'value' => 'Value2',                            // The value of the data passed for the key.
);
array_push($MerchantDataVars, $MerchantDataVar);

$PayPalRequestData = array(
    'RTFields'=>$RTFields,
    'MerchantDataVars' => $MerchantDataVars,
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);