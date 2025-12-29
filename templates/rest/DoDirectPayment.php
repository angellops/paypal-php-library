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
$DPFields = array(
    'paymentaction' => '', 		// How you want to obtain payment.  Authorization indicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
    'ipaddress' => '', 			// Required.  IP address of the payer's browser.
    'returnfmfdetails' => '', 		// Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
    'softdescriptor' => '',    		// Information that is usually displayed in the account holder's statement.  23 char max.
    'softdescriptorcity' => '',         // A unique phone number, emaili address or URL, which is displayed on the account holder's statement.
);
				
$CCDetails = array(
	'creditcardtype' => '', 	// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
	'acct' => '', 			// Required.  Credit card number.  No spaces or punctuation.  
	'expdate' => '', 		// Required.  Credit card expiration date.  Format is MMYYYY
	'cvv2' => '', 			// Requirements determined by your PayPal account settings.  Security digits for credit card.
	'startdate' => '', 		// Month and year that Maestro or Solo card was issued.  MMYYYY
	'issuenumber' => ''		// Issue number of Maestro or Solo card.  Two numeric digits max.
);
				
$PayerInfo = array(
	'email' => '', 			// Email address of payer.
	'firstname' => '', 		// Required.  Payer's first name.
	'lastname' => '' 		// Required.  Payer's last name.
);
				
$BillingAddress = array(
	'street' => '', 		// Required.  First street address.
	'street2' => '', 		// Second street address.
	'city' => '', 			// Required.  Name of City.
	'state' => '', 			// Required. Name of State or Province.
	'countrycode' => '', 		// Required.  Country code.
	'zip' => '', 			// Required.  Postal code of payer.
	'phonenum' => '' 		// Phone Number of payer.  20 char max.
);
					
$ShippingAddress = array(
	'shiptoname' => '', 		// Required if shipping is included.  Person's name associated with this address.  32 char max.
	'shiptostreet' => '', 		// Required if shipping is included.  First street address.  100 char max.
	'shiptostreet2' => '', 		// Second street address.  100 char max.
	'shiptocity' => '', 		// Required if shipping is included.  Name of city.  40 char max.
	'shiptostate' => '', 		// Required if shipping is included.  Name of state or province.  40 char max.
	'shiptozip' => '', 		// Required if shipping is included.  Postal code of shipping address.  20 char max.
	'shiptocountry' => '', 		// Required if shipping is included.  Country code of shipping address.  2 char max.
	'shiptophonenum' => ''		// Phone number for shipping address.  20 char max.
);
					
$PaymentDetails = array(
	'amt' => '', 			// Required.  Total amount of order, including shipping, handling, and tax.  
	'currencycode' => '', 		// Required.  Three-letter currency code.  Default is USD.
	'itemamt' => '', 		// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
	'shippingamt' => '', 		// Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
	'insuranceamt' => '', 		// Total shipping insurance costs for this order.  
	'shipdiscamt' => '', 		// Shipping discount for the order, specified as a negative number.
	'handlingamt' => '', 		// Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
	'taxamt' => '', 		// Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax. 
	'desc' => '', 			// Description of the order the customer is purchasing.  127 char max.
	'custom' => '', 		// Free-form field for your own use.  256 char max.
	'invnum' => '', 		// Your own invoice or tracking number
	'notifyurl' => '', 		// URL for receiving Instant Payment Notifications.  This overrides what your profile is set to use.
	'recurring' => ''		// Flag to indicate a recurring transaction.  Value should be Y for recurring, or anything other than Y if it's not recurring.  To pass Y here, you must have an established billing agreement with the buyer.
);

// For order items you populate a nested array with multiple $Item arrays.  Normally you'll be looping through cart items to populate the $Item 
// array and then push it into the $OrderItems array at the end of each loop for an entire collection of all items in $OrderItems.

$OrderItems = array();		
	
$Item	 = array(
	'l_name' => '', 			// Item Name.  127 char max.
	'l_desc' => '', 			// Item description.  127 char max.
	'l_amt' => '', 				// Cost of individual item.
	'l_number' => '', 			// Item Number.  127 char max.
	'l_qty' => '', 				// Item quantity.  Must be any positive integer.  
	'l_taxamt' => '', 			// Item's sales tax amount.
	'l_ebayitemnumber' => '', 		// eBay auction number of item.
	'l_ebayitemauctiontxnid' => '', 	// eBay transaction ID of purchased item.
	'l_ebayitemorderid' => '' 		// eBay order ID for the item.
);

array_push($OrderItems, $Item);

/**
 * Important note : 3-D Secure Request Fields (UK Merchants Only)
 */
$Secure3D = array(
	'authstatus3ds' => '', 		// (Optional) A value returned by CardinalCommerce. If the cmpi_lookup request returns Y for Enrolled, set this field to the PAResStatus value returned by cmpi_authenticate. Otherwise, set this field to blank.
	'mpivendor3ds' => '', 		// (Optional) A value returned by CardinalCommerce. Set this field to the Enrolled value returned by cmpi_lookup.
	'cavv' => '', 			// (Optional) A value returned by CardinalCommerce. If the cmpi_lookup request returns Y for Enrolled, set this field to the Cavv value returned by cmpi_authenticate. Otherwise, set this field to blank.	
	'eci3ds' => '',			// (Optional) A value returned by CardinalCommerce. If the cmpi_lookup request returns Y for Enrolled, set this field to the EciFlag value returned by cmpi_authenticate. Otherwise, set this field to the EciFlag value returned by cmpi_lookup.
	'xid' => ''			// (Optional) A value returned by CardinalCommerce. If the cmpi_lookup request returns Y for Enrolled, set this field to the Xid value returned by cmpi_authenticate. Otherwise, set this field to blank.	
);
				  
$PayPalRequestData = array(
	'DPFields' => $DPFields, 
	'CCDetails' => $CCDetails, 
	'PayerInfo' => $PayerInfo, 
	'BillingAddress' => $BillingAddress, 
	'ShippingAddress' => $ShippingAddress, 
	'PaymentDetails' => $PaymentDetails, 
	'OrderItems' => $OrderItems,
	'Secure3D' => $Secure3D,
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->DoDirectPayment($PayPalRequestData);

$_SESSION['transaction_id'] = isset($PayPalResult['RESPONSE']['id']) ? $PayPalResult['RESPONSE']['id'] : '';

echo '<pre />';
print_r($PayPalResult);
