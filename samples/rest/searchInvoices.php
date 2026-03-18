<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'IPAddress' => $_SERVER['REMOTE_ADDR'],
	'PayPalAPIMode' => $api_mode,
    'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Prepare request arrays
$SearchInvoicesFields = array(
    'Page' => '1', 								// Required.  Page number of result set, starting with 1
	'PageSize' => '20'							// Required.  Number of result pages, between 1 and 100
);

$Parameters = array(
    'Email' => '', 								// Email search string
    'MerchantEmail' => '',                      // Required.  Email address of invoice creator.
	'InvoiceNumber' => '', 						// Invoice number search string
	'Status' => 'DRAFT', 						// Invoice status search
	'LowerAmount' => '', 						// Invoice amount search.  It specifies the smallest amount to be returned.  If you pass a value for this field, you must also pass a CurrencyCode value.
	'UpperAmount' => '', 						// Invoice amount search.  It specifies the largest amount to be returned.  If you pass a value for this field, you must also pass a CurrencyCode value.
	'CurrencyCode' => '', 						// Currency used for lower and upper amounts.  
	'Memo' => '', 								// Invoice memo search string
);

$PayPalRequestData = array(
	'SearchInvoicesFields' => $SearchInvoicesFields, 
	'Parameters' => $Parameters
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->searchInvoices($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
