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
$DRTFields = array(
    'referenceid' => '', 						// Required.  A transaction ID from a previous purchase, such as a credit card charage using DoDirectPayment, or a billing agreement ID
    'paymentaction' => '', 						// How you want to obtain payment.  Values are:  Authorization, Sale
    'paymenttype' => '', 						// Specifies type of PayPal payment you require for the billing agreement.  Values are:  Any, InstantOnly
    'ipaddress' => '', 							// IP address of the buyer's browser
    'reqconfirmshipping' => '', 					// Whether you require that the buyer's shipping address on file with PayPal be a confirmed address or not.  Values are 0/1
    'returnfmfdetails' => '', 					// Flag to indicate whether you want the results returned by Fraud Management Filters.  Values are 0/1
    'softdescriptor' => '',                     // Information that is usually displayed in the account holder's statement.  23 char max.
    'softdescriptorcity' => '',                 // A unique phone number, email address or URL, which is displayed on the account holder's statement.
    'msgsubid' => ''								// A message ID used for idempotence to uniquely identify a message.
);

$ShippingAddress = array(
						'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
						'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
						'shiptostreet2' => '', 					// Second street address.  100 char max.
						'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
						'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
						'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
						'shiptocountry' => '', 					// Required if shipping is included.  Country code of shipping address.  2 char max.
						'shiptophonenum' => ''					// Phone number for shipping address.  20 char max.
						);

$PaymentDetails = array(
						'amt' => '', 							// Required. Total amount of the order, including shipping, handling, and tax.
						'currencycode' => '', 					// A three-character currency code.  Default is USD.
						'itemamt' => '', 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
						'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
						'insuranceamt' => '', 
						'shippingdiscount' => '', 
						'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
						'taxamt' => '', 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
						'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays Yes and shows the amount.
						'desc' => '', 							// Description of items on the order.  127 char max.
						'custom' => '', 						// Free-form field for your own use.  256 char max.
						'invnum' => '', 						// Your own invoice or tracking number.  127 char max.
						'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
						'recurring' => ''						// Flag to indicate a recurring transaction.  Values are:  Y for recurring.  Anything other than Y is not recurring.
						);

// For order items you populate a nested array with multiple $Item arrays.  Normally you'll be looping through cart items to populate the $Item 
// array and then push it into the $OrderItems array at the end of each loop for an entire collection of all items in $OrderItems.

$OrderItems = array();
$Item = array(
			'l_itemcategory' => '', 					// One of the following values:  Digital, Physical
			'l_name' => '', 							// Item name. 127 char max.
			'l_description' => '', 						// Item description.  127 char max.
			'l_amt' => '', 								// Cost of item.
			'l_number' => '', 							// Item number.  127 char max.
			'l_qty' => '', 								// Item qty on order.  Any positive integer.
			'l_taxamt' => '', 							// Item sales tax
			'l_itemweightvalue' => '', 					// The weight value of the item.
			'l_itemweightunit' => '', 					// The weight unit of the item.
			'l_itemheightvalue' => '', 					// The height value of the item.
			'l_itemheightunit' => '', 					// The height unit of the item.
			'l_itemwidthvalue' => '', 					// The width value of the item.
			'l_itemwidthunit' => '', 					// The width unit of the item.
			'l_itemlengthvalue' => '', 					// The length value of the item.
			'l_itemlengthunit' => '',  					// The length unit of the item.
			'l_ebayitemnumber' => '', 					// Auction item number.  
			'l_ebayitemauctiontxnid' => '', 			// Auction transaction ID number.  
			'l_ebayitemorderid' => '' 					// Auction order ID number.
			);
					
array_push($OrderItems, $Item);

$CCDetails = array(
					'creditcardtype' => '', 					// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
					'acct' => '', 								// Required.  Credit card number.  No spaces or punctuation.  
					'expdate' => '', 							// Required.  Credit card expiration date.  Format is MMYYYY
					'cvv2' => '', 								// Requirements determined by your PayPal account settings.  Security digits for credit card.
					'startdate' => '', 							// Month and year that Maestro or Solo card was issued.  MMYYYY
					'issuenumber' => ''							// Issue number of Maestro or Solo card.  Two numeric digits max.
				);

$PayerInfo = array(
					'email' => '', 								// Email address of payer.
					'firstname' => '', 							// Unique PayPal customer ID for payer.
					'lastname' => ''						// Status of payer.  Values are verified or unverified
				);
				
$BillingAddress = array(
						'street' => '', 						// Required.  First street address.
						'street2' => '', 						// Second street address.
						'city' => '', 							// Required.  Name of City.
						'state' => '', 							// Required. Name of State or Province.
						'countrycode' => '', 					// Required.  Country code.
						'zip' => '', 							// Required.  Postal code of payer.
						'phonenum' => '' 						// Phone Number of payer.  20 char max.
					);
					
$PayPalRequestData = array(
						'DRTFields' => $DRTFields, 
						'ShippingAddress' => $ShippingAddress, 
						'PaymentDetails' => $PaymentDetails, 
						'OrderItems' => $OrderItems, 
						'CCDetails' => $CCDetails, 
						'PayerInfo' => $PayerInfo, 
						'BillingAddress' => $BillingAddress
						);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->DoReferenceTransaction($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
