<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'DeveloperAccountEmail' => $developer_account_email,
	'ApplicationID' => $application_id,
	'DeviceID' => $device_id,
	'IPAddress' => $_SERVER['REMOTE_ADDR'],
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'APISubject' => $api_subject,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$invoiceDetails = array(
        "currency_code" => "USD",               // The three-character ISO 4217 currency code.
        "note" => "This is a test invoice.",    // A note to the payer.
        "term" => "DueOnReceipt",               // The payment term of the invoice, such as DueOnReceipt, Net30, etc.
        "memo" => "This is a test invoice.",    // A memo for the invoice.
);

$nameData = array(
        "given_name" => "Tester",               // The first name of the person.
        "surname" => "Testerson"                // The last name of the person.
);

$phonesData = array(); 

$phoneData = array(
        "country_code" => "1",                  // The country code of the phone number.
        "national_number" => "5555555555",      // The phone number without the country code.
        "phone_type" => "MOBILE"                // The type of phone number.  Valid Values:  MOBILE, HOME, WORK, FAX.
);
array_push($phonesData,$phoneData);

$addressData = array(
        "address_line_1" => "123 Main St.",             // The first line of the address.
        "admin_area_2" => "Grandview",                  // The city of the address.
        "admin_area_1" => "MO",                         // The state of the address.
        "postal_code" => "64030",                       // The postal code of the address.
        "country_code" => "US"                          // The country code of the address.
);

$InvoiceItems = array();

$InvoiceItem = array(
	'name' => 'Test Widget 1', 			        // Required.  SKU or name of the item.	
	'description' => 'This is a test widget #1', 		// Item description.
	'quantity' => '1', 					// Required. Item count. Values are:  0 to 10000
        'unit_amount' => array(
                'currency_code' => 'USD',                       // The currency code.
                'value' => '10.00'                              // The amount for the item.
        ),
);
array_push($InvoiceItems,$InvoiceItem);

$InvoiceItem = array(
	'name' => 'Test Widget 2', 				// Required.  SKU or name of the item.
	'description' => 'This is a test widget #2', 		// Item description.
	'quantity' => '2', 					// Required. Item count. Values are:  0 to 10000
        'unit_amount' => array(
                'currency_code' => 'USD',                       // The currency code.
                'value' => '20.00'                              // The amount for the item.
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
                "email_address" => "sb-j47z3s6571663@business.example.com",
                "phones" => $phonesData,
                "website" => "http://www.domain.com",
                "address" => $addressData
        ),
        "primary_recipients" => array(
                array(
                        "billing_info" => array(
                                "email_address" => "sb-j47z3s6571663@business.example.com",
                                "name" => $nameData,
                                "address" => $addressData
                        ),
                )
        ),
        "items" => $InvoiceItems,
        "configuration" => $configurationData
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->CreateInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
