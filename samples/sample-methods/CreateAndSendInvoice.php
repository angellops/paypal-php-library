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
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'APISubject' => $api_subject,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
	'isAdaptive' => true,
        'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

if( $api_mode === 'rest' ) {

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
        $PayPalResult = $PayPal->CreateAndSendInvoice($PayPalRequestData);

        // Write the contents of the response array to the screen for demo purposes.
        echo '<pre />';

} else {
        // Prepare request arrays
        $CreateInvoiceFields = array(
                'MerchantEmail' => 'sandbo_1215254764_biz@angelleye.com', 				// Required.  Merchant email address.
                'PayerEmail' => 'drew@angelleye.com', 							// Required.  Payer email address.
                'Number' => 'ABC-ZZZz', 								// Unique ID for the invoice.
                'CurrencyCode' => 'USD', 								// Required.  Currency used for all invoice item amounts and totals.
                'InvoiceDate' => '', 									// Date on which the invoice is enabled.
                'DueDate' => '', 									// Date on which the invoice payment is due.
                'PaymentTerms' => 'DueOnReceipt', 							// Required.  Terms by which the invoice payment is due.  Values are:  DueOnReceipt, DueOnSpecified, Net10, Net15, Net30, Net45
                'DiscountPercent' => '', 								// Discount percent applied to the invoice.
                'DiscountAmount' => '', 								// Discount amount applied to the invoice.  If DiscountPercent is provided, DiscountAmount is ignored.
                'Terms' => '', 										// General terms for the invoice.
                'Note' => 'This is a test invoice.', 							// Note to the payer company.
                'MerchantMemo' => 'This is a test invoice.', 						// Memo for bookkeeping that is private to the merchant.
                'ShippingAmount' => '10.00', 								// Cost of shipping
                'ShippingTaxName' => '', 								// Name of the applicable tax on the shipping cost.
                'ShippingTaxRate' => '', 								// Rate of the applicable tax on the shipping cost.
                'LogoURL' => 'https://www.usbswiper.com/images/angelley-clients/cpp-header-image.jpg'	// Complete URL to an external image used as the logo, if any.
        );
                                                                
        $BusinessInfo = array(
                'FirstName' => 'Tester', 			// First name of the company contact.
                'LastName' => 'Testerson', 			// Last name of the company contact.
                'BusinessName' => 'Testers, LLC', 		// Company business name.
                'Phone' => '555-555-5555', 			// Phone number for contacting the company.
                'Fax' => '555-555-5556', 			// Fax number used by the company.
                'Website' => 'http://www.domain.com', 		// Website used by the company.
                'Custom' => 'Some custom info.' 		// Custom value to be displayed in the contact information details.
        );
                                                
        $BusinessInfoAddress = array(
                'Line1' => '123 Main St.', 		// Required. First line of address.
                'Line2' => '', 				// Second line of address.
                'City' => 'Grandview', 			// Required. City of the address.
                'State' => 'MO', 			// State for the address.
                'PostalCode' => '64030', 		// Postal code of the address
                'CountryCode' => 'US'			// Required.  Country code of the address.
        );

        $BillingInfo = array(
                'FirstName' => '', 		// First name of the company contact.
                'LastName' => '', 		// Last name of the company contact.
                'BusinessName' => '', 		// Company business name.
                'Phone' => '', 			// Phone number for contacting the company.
                'Fax' => '', 			// Fax number used by the company.
                'Website' => '', 		// Website used by the company.
                'Custom' => '' 			// Custom value to be displayed in the contact information details.
        );
                                                
        $BillingInfoAddress = array(
                'Line1' => '', 			// Required. First line of address.
                'Line2' => '', 			// Second line of address.
                'City' => '', 			// Required. City of the address.
                'State' => '', 			// State for the address.
                'PostalCode' => '', 		// Postal code of the address
                'CountryCode' => ''		// Required.  Country code of the address.
        );

        $ShippingInfo = array(
                'FirstName' => '', 		// First name of the company contact.
                'LastName' => '', 		// Last name of the company contact.
                'BusinessName' => '', 		// Company business name.
                'Phone' => '', 			// Phone number for contacting the company.
                'Fax' => '', 			// Fax number used by the company.
                'Website' => '', 		// Website used by the company.
                'Custom' => '' 			// Custom value to be displayed in the contact information details.
        );
                                                
        $ShippingInfoAddress = array(
                'Line1' => '', 			// Required. First line of address.
                'Line2' => '', 			// Second line of address.
                'City' => '', 			// Required. City of the address.
                'State' => '', 			// State for the address.
                'PostalCode' => '', 		// Postal code of the address
                'CountryCode' => ''		// Required.  Country code of the address.
        );

        // For invoice items you populate a nested array with multiple $InvoiceItem arrays.  Normally you'll be looping through cart items to populate the $InvoiceItem 
        // array and then push it into the $InvoiceItems array at the end of each loop for an entire collection of all items in $InvoiceItems.

        $InvoiceItems = array();

        $InvoiceItem = array(
                'Name' => 'Test Widget 1', 				// Required.  SKU or name of the item.
                'Description' => 'This is a test widget #1', 		// Item description.
                'Date' => '2012-02-18', 				// Date on which the product or service was provided.
                'Quantity' => '1', 					// Required.  Item count.  Values are:  0 to 10000
                'UnitPrice' => '10.00', 				// Required.  Price of the item, in the currency specified by the invoice.
                'TaxName' => '', 					// Name of the applicable tax.
                'TaxRate' => ''						// Rate of the applicable tax.
        );
        array_push($InvoiceItems,$InvoiceItem);

        $InvoiceItem = array(
                'Name' => 'Test Widget 2', 				// Required.  SKU or name of the item.
                'Description' => 'This is a test widget #2', 		// Item description.
                'Date' => '2012-02-18', 				// Date on which the product or service was provided.
                'Quantity' => '2', 					// Required.  Item count.  Values are:  0 to 10000
                'UnitPrice' => '20.00', 				// Required.  Price of the item, in the currency specified by the invoice.
                'TaxName' => '', 					// Name of the applicable tax.
                'TaxRate' => ''						// Rate of the applicable tax.
        );
        array_push($InvoiceItems,$InvoiceItem);

        $PayPalRequestData = array(
                'CreateInvoiceFields' => $CreateInvoiceFields, 
                'BusinessInfo' => $BusinessInfo, 
                //'BusinessInfoAddress' => $BusinessInfoAddress, 
                //'BillingInfo' => $BillingInfo, 
                //'BillingInfoAddress' => $BillingInfoAddress, 
                //'ShippingInfo' => $ShippingInfo, 
                //'ShippingInfoAddress' => $ShippingInfoAddress, 
                'InvoiceItems' => $InvoiceItems
        );

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->Adaptive->CreateAndSendInvoice($PayPalRequestData);

        // Write the contents of the response array to the screen for demo purposes.
        echo '<pre />';
        echo "<p><strong>Deprecated Notice:</strong> The classic CreateAndSendInvoice method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
}


print_r($PayPalResult);
