<?php
require_once('../../includes/config.php');
require_once('../../autoload.php');

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

$DRTFields = array(
	'referenceid' => 'B-26G90383GW321384N', 
	'paymentaction' => 'Sale', 
	'returnfmfdetails' => '1', 
	'softdescriptor' => 'Angell EYE'
);

$ShippingAddress = array(
	'shiptoname' => 'John Doe', 				// Required if shipping is included.  Person's name associated with this address.  32 char max.
	'shiptostreet' => '1 Main St', 				// Required if shipping is included.  First street address.  100 char max.
	'shiptostreet2' => '', 					// Second street address.  100 char max.
	'shiptocity' => 'San Jose', 				// Required if shipping is included.  Name of city.  40 char max.
	'shiptostate' => 'CA', 					// Required if shipping is included.  Name of state or province.  40 char max.
	'shiptozip' => '95131', 				// Required if shipping is included.  Postal code of shipping address.  20 char max.
	'shiptocountry' => 'US', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
	'shiptophonenum' => ''					// Phone number for shipping address.  20 char max.
);

$PaymentDetails = array(
	'amt' => '10.00', 					// Required. Total amount of the order, including shipping, handling, and tax.
	'currencycode' => 'USD', 				// A three-character currency code.  Default is USD.
	'itemamt' => '', 					// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
	'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
	'insuranceamt' => '', 
	'shippingdiscount' => '', 
	'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
	'taxamt' => '', 					// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
	'insuranceoptionoffered' => '', 			// If true, the insurance drop-down on the PayPal review page displays Yes and shows the amount.
	'desc' => 'Angell EYE Invoice - Final Payment for MiltonsBells.com', 							// Description of items on the order.  127 char max.
	'custom' => '', 					// Free-form field for your own use.  256 char max.
	'invnum' => '35', 					// Your own invoice or tracking number.  127 char max.
	'buttonsource' => '', 					// ID code for use by third-party apps to identify transactions in PayPal. 
	'notifyurl' => '' 					// URL for receiving Instant Payment Notifications
);

$OrderItems = array();
$Item = array(
	'l_name' => '', 						// Item name. 127 char max.
	'l_description' => '', 
	'l_amt' => '', 							// Cost of item.
	'l_number' => '', 						// Item number.  127 char max.
	'l_qty' => '', 							// Item qty on order.  Any positive integer.
	'l_taxamt' => '', 						// Item sales tax
	'l_itemweightvalue' => '', 					// The weight value of the item.
	'l_itemweightunit' => '', 					// The weight unit of the item.
	'l_itemheightvalue' => '', 					// The height value of the item.
	'l_itemheightunit' => '', 					// The height unit of the item.
	'l_itemwidthvalue' => '', 					// The width value of the item.
	'l_itemwidthunit' => '', 					// The width unit of the item.
	'l_itemlengthvalue' => '', 					// The length value of the item.
	'l_itemlengthunit' => '',  					// The length unit of the item.
	'l_ebayitemnumber' => '', 					// Auction item number.  
	'l_ebayitemauctiontxnid' => '', 				// Auction transaction ID number.  
	'l_ebayitemorderid' => '' 					// Auction order ID number.
);			
array_push($OrderItems, $Item);

$CCDetails = array(
	'creditcardtype' => '', 					// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
	'acct' => '', 							// Required.  Credit card number.  No spaces or punctuation.  
	'expdate' => '', 						// Required.  Credit card expiration date.  Format is MMYYYY
	'cvv2' => '', 							// Requirements determined by your PayPal account settings.  Security digits for credit card.
	'startdate' => '', 						// Month and year that Maestro or Solo card was issued.  MMYYYY
	'issuenumber' => ''						// Issue number of Maestro or Solo card.  Two numeric digits max.
);

$PayerInfo = array(
	'email' => '', 							// Email address of payer.
	'firstname' => '', 						// Unique PayPal customer ID for payer.
	'lastname' => ''						// Status of payer.  Values are verified or unverified
);
				
$BillingAddress = array(
	'street' => '', 						// Required.  First street address.
	'street2' => '', 						// Second street address.
	'city' => '', 							// Required.  Name of City.
	'state' => '', 							// Required. Name of State or Province.
	'countrycode' => '', 						// Required.  Country code.
	'zip' => '', 							// Required.  Postal code of payer.
	'phonenum' => '' 						// Phone Number of payer.  20 char max.
);

$PayPalRequestData = array(
	'DRTFields' => $DRTFields, 
	'PaymentDetails' => $PaymentDetails 
);

$PayPalResult = $PayPal -> DoReferenceTransaction($PayPalRequestData);

echo '<pre />';
print_r($PayPalResult);
