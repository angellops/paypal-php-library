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

$invoiceDetails = array(
    "currency_code" => "",              // The three-character ISO 4217 currency code.
    "note" => "",                       // A note to the payer.
    "term" => "",                       // The payment term of the invoice, such as DueOnReceipt, Net30, etc.
    "memo" => "",                       // A memo for the invoice.
);

$nameData = array(
    "given_name" => "",                 // The first name of the person.
    "surname" => ""                     // The last name of the person.
);

$phonesData = array(); 
$phoneData = array(
    "country_code" => "",               // The country code of the phone number.
    "national_number" => "",            // The phone number without the country code.
    "phone_type" => ""                  // The type of phone number.  Valid Values:  MOBILE, HOME, WORK, FAX.
);
array_push($phonesData,$phoneData);

$addressData = array(
    "address_line_1" => "",             // The first line of the address.
    "admin_area_2" => "",               // The city of the address.
    "admin_area_1" => "",               // The state of the address.
    "postal_code" => "",                // The postal code of the address.
    "country_code" => ""                // The country code of the address.
);

$InvoiceItems = array();

$InvoiceItem = array(
	"name" => "", 			            // Required.  SKU or name of the item.	
	"description" => "", 		        // Item description.
	"quantity" => "", 					// Required. Item count. Values are:  0 to 10000
    "unit_amount" => array(
        "currency_code" => "",          // The currency code.
        "value" => ""                   // The amount for the item.
    ),
);
array_push($InvoiceItems,$InvoiceItem);

$InvoiceItem = array(
	"name" => "", 			            // Required.  SKU or name of the item.	
	"description" => "", 		        // Item description.
	"quantity" => "", 					// Required. Item count. Values are:  0 to 10000
    "unit_amount" => array(
        "currency_code" => "",          // The currency code.
        "value" => ""                   // The amount for the item.
    ),
);
array_push($InvoiceItems,$InvoiceItem);

$configurationData = array(
    "partial_payment" => array("allow_partial_payment" => false),   // Indicates whether to allow partial payments.  true or false.
    "tax_calculated_after_discount" => true,                        // Indicates whether the tax amount is calculated after applying the discount.  true or false.
    "allow_tip" => false,                                           // Indicates whether to allow the payer to add a tip to the invoice.  true or false.
);

$PayPalRequestData = array(
    "detail" => $invoiceDetails,
    "invoicer" => array(
        "name" => $nameData,
        "email_address" => "",
        "phones" => $phonesData,
        "website" => "",
        "address" => $addressData
    ),
    "primary_recipients" => array(
        array(
            "billing_info" => array(
                "email_address" => "",
                "name" => $nameData,
                "address" => $addressData
            ),
        )
    ),
    "items" => $InvoiceItems,
    "configuration" => $configurationData
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->createAndSendInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
