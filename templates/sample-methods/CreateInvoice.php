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

if( $api_mode === 'classic' ){

        // Prepare request arrays
        $CreateInvoiceFields = array(
                'MerchantEmail' => '', 				// Required.  Merchant email address.
                'PayerEmail' => '', 					// Required.  Payer email address.
                'Number' => '', 									// Unique ID for the invoice.
                'CurrencyCode' => '', 								// Required.  Currency used for all invoice item amounts and totals.
                'InvoiceDate' => '', 									// Date on which the invoice is enabled.
                'DueDate' => '', 									// Date on which the invoice payment is due.
                'PaymentTerms' => '', 							// Required.  Terms by which the invoice payment is due.  Values are:  DueOnReceipt, DueOnSpecified, Net10, Net15, Net30, Net45
                'DiscountPercent' => '', 								// Discount percent applied to the invoice.
                'DiscountAmount' => '', 								// Discount amount applied to the invoice.  If DiscountPercent is provided, DiscountAmount is ignored.
                'Terms' => '', 										// General terms for the invoice.
                'Note' => '', 							// Note to the payer company.
                'MerchantMemo' => '', 						// Memo for bookkeeping that is private to the merchant.
                'ShippingAmount' => '', 								// Cost of shipping
                'ShippingTaxName' => '', 								// Name of the applicable tax on the shipping cost.
                'ShippingTaxRate' => '', 								// Rate of the applicable tax on the shipping cost.
                'LogoURL' => ''	// Complete URL to an external image used as the logo, if any.
        );
                                                                
        $BusinessInfo = array(
                'FirstName' => '', 			// First name of the company contact.
                'LastName' => '', 			// Last name of the company contact.
                'BusinessName' => '', 		// Company business name.
                'Phone' => '', 			// Phone number for contacting the company.
                'Fax' => '', 			// Fax number used by the company.
                'Website' => '', 		// Website used by the company.
                'Custom' => '' 		// Custom value to be displayed in the contact information details.
        );
                                                
        $BusinessInfoAddress = array(
                'Line1' => '', 			// Required. First line of address.
                'Line2' => '', 					// Second line of address.
                'City' => '', 				// Required. City of the address.
                'State' => '', 				// State for the address.
                'PostalCode' => '', 			// Postal code of the address
                'CountryCode' => ''				// Required.  Country code of the address.
        );

        $BillingInfo = array(
                'FirstName' => '', 			// First name of the company contact.
                'LastName' => '', 			// Last name of the company contact.
                'BusinessName' => '', 		// Company business name.
                'Phone' => '', 			// Phone number for contacting the company.
                'Fax' => '', 			// Fax number used by the company.
                'Website' => '', 		// Website used by the company.
                'Custom' => '' 		// Custom value to be displayed in the contact information details.
        );
                                                
        $BillingInfoAddress = array(
                'Line1' => '', 			// Required. First line of address.
                'Line2' => '', 					// Second line of address.
                'City' => '', 				// Required. City of the address.
                'State' => '', 				// State for the address.
                'PostalCode' => '', 			// Postal code of the address
                'CountryCode' => ''				// Required.  Country code of the address.
        );

        $ShippingInfo = array(
                'FirstName' => '', 			// First name of the company contact.
                'LastName' => '', 			// Last name of the company contact.
                'BusinessName' => '', 		// Company business name.
                'Phone' => '', 			// Phone number for contacting the company.
                'Fax' => '', 			// Fax number used by the company.
                'Website' => '', 		// Website used by the company.
                'Custom' => '' 		// Custom value to be displayed in the contact information details.
        );
                                                
        $ShippingInfoAddress = array(
                'Line1' => '', 		// Required. First line of address.
                'Line2' => '', 				// Second line of address.
                'City' => '', 			// Required. City of the address.
                'State' => '', 			// State for the address.
                'PostalCode' => '', 		// Postal code of the address
                'CountryCode' => ''			// Required.  Country code of the address.
        );

        // For invoice items you populate a nested array with multiple $InvoiceItem arrays.  Normally you'll be looping through cart items to populate the $InvoiceItem 
        // array and then push it into the $InvoiceItems array at the end of each loop for an entire collection of all items in $InvoiceItems.

        $InvoiceItems = array();

        $InvoiceItem = array(
                'Name' => '', 				// Required.  SKU or name of the item.
                'Description' => '', 		// Item description.
                'Date' => '', 				// Date on which the product or service was provided.
                'Quantity' => '', 					// Required.  Item count.  Values are:  0 to 10000
                'UnitPrice' => '', 				// Required.  Price of the item, in the currency specified by the invoice.
                'TaxName' => '', 					// Name of the applicable tax.
                'TaxRate' => ''						// Rate of the applicable tax.
        );
        array_push($InvoiceItems,$InvoiceItem);

        $InvoiceItem = array(
                'Name' => '', 				// Required.  SKU or name of the item.
                'Description' => '', 		// Item description.
                'Date' => '', 				// Date on which the product or service was provided.
                'Quantity' => '', 					// Required.  Item count.  Values are:  0 to 10000
                'UnitPrice' => '', 				// Required.  Price of the item, in the currency specified by the invoice.
                'TaxName' => '', 					// Name of the applicable tax.
                'TaxRate' => ''						// Rate of the applicable tax.
        );
        array_push($InvoiceItems,$InvoiceItem);

        $PayPalRequestData = array(
                'CreateInvoiceFields' => $CreateInvoiceFields, 
                'BusinessInfo' => $BusinessInfo, 
                'BusinessInfoAddress' => $BusinessInfoAddress, 
                'BillingInfo' => $BillingInfo, 
                'BillingInfoAddress' => $BillingInfoAddress, 
                'ShippingInfo' => $ShippingInfo, 
                'ShippingInfoAddress' => $ShippingInfoAddress, 
                'InvoiceItems' => $InvoiceItems
        );

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->Adaptive->CreateInvoice($PayPalRequestData);
        echo '<pre />';
        echo "<p><strong>Deprecated Notice:</strong> The classic CreateInvoice method your plugin/theme is using has been deprecated. Please upgrade to the new REST-based implementation to ensure compatibility with future updates.</p>";
} else {
        $invoiceDetails = array(
                "currency_code" => "",               // The three-character ISO 4217 currency code.
                "note" => "",    // A note to the payer.
                "term" => "",               // The payment term of the invoice, such as DueOnReceipt, Net30, etc.
                "memo" => "",    // A memo for the invoice.
        );

        $nameData = array(
                "given_name" => "",               // The first name of the person.
                "surname" => ""                // The last name of the person.
        );

        $phonesData = array(); 

        $phoneData = array(
                "country_code" => "",                  // The country code of the phone number.
                "national_number" => "",      // The phone number without the country code.
                "phone_type" => ""                // The type of phone number.  Valid Values:  MOBILE, HOME, WORK, FAX.
        );
        array_push($phonesData,$phoneData);

        $addressData = array(
                "address_line_1" => "",             // The first line of the address.
                "admin_area_2" => "",                  // The city of the address.
                "admin_area_1" => "",                         // The state of the address.
                "postal_code" => "",                       // The postal code of the address.
                "country_code" => ""                          // The country code of the address.
        );

        $InvoiceItems = array();

        $InvoiceItem = array(
                'name' => '', 			        // Required.  SKU or name of the item.	
                'description' => '', 		// Item description.
                'quantity' => '', 					// Required. Item count. Values are:  0 to 10000
                'unit_amount' => array(
                        'currency_code' => '',                       // The currency code.
                        'value' => ''                              // The amount for the item.
                ),
        );
        array_push($InvoiceItems,$InvoiceItem);

        $InvoiceItem = array(
                'name' => '', 				// Required.  SKU or name of the item.
                'description' => '', 		// Item description.
                'quantity' => '', 					// Required. Item count. Values are:  0 to 10000
                'unit_amount' => array(
                        'currency_code' => '',                       // The currency code.
                        'value' => ''                              // The amount for the item.
                ),
        );
        array_push($InvoiceItems,$InvoiceItem);

        $configurationData = array(
                "partial_payment" => array("allow_partial_payment" => false),   // Indicates whether to allow partial payments.  true or false.
                "tax_calculated_after_discount" => '',                        // Indicates whether the tax amount is calculated after applying the discount.  true or false.
                "allow_tip" => '',                                           // Indicates whether to allow the payer to add a tip to the invoice.  true or false.
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
        $PayPalResult = $PayPal->CreateInvoice($PayPalRequestData);
        echo '<pre />';
}
print_r($PayPalResult);
