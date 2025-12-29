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
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$CreateInvoiceFields = array(
	'MerchantEmail' => 'sandbo_1215254764_biz@angelleye.com', 				// Required.  Merchant email address.
	'PayerEmail' => 'sandbo_1204199080_biz@angelleye.com', 					// Required.  Payer email address.
	'Number' => '', 									// Unique ID for the invoice.
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
	'Line1' => '123 Main St.', 			// Required. First line of address.
	'Line2' => '', 					// Second line of address.
	'City' => 'Grandview', 				// Required. City of the address.
	'State' => 'MO', 				// State for the address.
	'PostalCode' => '64030', 			// Postal code of the address
	'CountryCode' => 'US'				// Required.  Country code of the address.
);

$BillingInfo = array(
	'FirstName' => 'Tester', 			// First name of the company contact.
	'LastName' => 'Testerson', 			// Last name of the company contact.
	'BusinessName' => 'Testers, LLC', 		// Company business name.
	'Phone' => '555-555-5555', 			// Phone number for contacting the company.
	'Fax' => '555-555-5556', 			// Fax number used by the company.
	'Website' => 'http://www.domain.com', 		// Website used by the company.
	'Custom' => 'Some custom info.' 		// Custom value to be displayed in the contact information details.
);
					
$BillingInfoAddress = array(
	'Line1' => '123 Main St.', 			// Required. First line of address.
	'Line2' => '', 					// Second line of address.
	'City' => 'Grandview', 				// Required. City of the address.
	'State' => 'MO', 				// State for the address.
	'PostalCode' => '64030', 			// Postal code of the address
	'CountryCode' => 'US'				// Required.  Country code of the address.
);

$ShippingInfo = array(
	'FirstName' => 'Tester', 			// First name of the company contact.
	'LastName' => 'Testerson', 			// Last name of the company contact.
	'BusinessName' => 'Testers, LLC', 		// Company business name.
	'Phone' => '555-555-5555', 			// Phone number for contacting the company.
	'Fax' => '555-555-5556', 			// Fax number used by the company.
	'Website' => 'http://www.domain.com', 		// Website used by the company.
	'Custom' => 'Some custom info.' 		// Custom value to be displayed in the contact information details.
);
					
$ShippingInfoAddress = array(
	'Line1' => '123 Main St.', 		// Required. First line of address.
	'Line2' => '', 				// Second line of address.
	'City' => 'Grandview', 			// Required. City of the address.
	'State' => 'MO', 			// State for the address.
	'PostalCode' => '64030', 		// Postal code of the address
	'CountryCode' => 'US'			// Required.  Country code of the address.
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
	'BusinessInfoAddress' => $BusinessInfoAddress, 
	'BillingInfo' => $BillingInfo, 
	'BillingInfoAddress' => $BillingInfoAddress, 
	'ShippingInfo' => $ShippingInfo, 
	'ShippingInfoAddress' => $ShippingInfoAddress, 
	'InvoiceItems' => $InvoiceItems
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->Adaptive->CreateInvoice($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
