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

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$orderId = '';
if( $api_mode === 'rest' && !$api_upgrade ) {
	$orderId = $_SESSION['SetExpressCheckoutResult']['order_id'];
} else {
	$orderId = $_SESSION['SetExpressCheckoutResult']['TOKEN'];
}

$payerID = '';

$DECPFields = array(
	'token' => $orderId, 	// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
	'payerid' => $payerID, 				// Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
	'returnfmfdetails' => '1', 					// Flag to indicate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
	'giftmessage' => '', 						// The gift message entered by the buyer on the PayPal Review page.  150 char max.
	'giftreceiptenable' => '', 					// Pass true if a gift receipt was selected by the buyer on the PayPal Review page. Otherwise pass false.
	'giftwrapname' => '', 						// The gift wrap name only if the gift option on the PayPal Review page was selected by the buyer.
	'giftwrapamount' => '', 					// The amount only if the gift option on the PayPal Review page was selected by the buyer.
	'buyermarketingemail' => '', 					// The buyer email address opted in by the buyer on the PayPal Review page.
	'surveyquestion' => '', 					// The survey question on the PayPal Review page.  50 char max.
	'surveychoiceselected' => '',  					// The survey response selected by the buyer on the PayPal Review page.  15 char max.
	'allowedpaymentmethod' => '', 					// The payment method type. Specify the value InstantPaymentOnly.
	'buttonsource' => '' 						// ID code for use by third-party apps to identify transactions in PayPal. 
);
						
$Payments = array();
$Payment = array(
	'amt' => '100.00', 								// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
	'currencycode' => 'USD', 							// A three-character currency code.  Default is USD.
	'itemamt' => '80.00', 								// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
	'shippingamt' => '15.00', 							// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
	'insuranceoptionoffered' => '', 						// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
	'handlingamt' => '', 								// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
	'taxamt' => '5.00', 								// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
	'desc' => 'This is a test order.', 						// Description of items on the order.  127 char max.
	'custom' => '', 								// Free-form field for your own use.  256 char max.
	'invnum' => '', 								// Your own invoice or tracking number.  127 char max.
	'notifyurl' => '',  								// URL for receiving Instant Payment Notifications
	'shiptoname' => '', 								// Required if shipping is included.  Person's name associated with this address.  32 char max.
	'shiptostreet' => '', 								// Required if shipping is included.  First street address.  100 char max.
	'shiptostreet2' => '', 								// Second street address.  100 char max.
	'shiptocity' => '', 								// Required if shipping is included.  Name of city.  40 char max.
	'shiptostate' => '', 								// Required if shipping is included.  Name of state or province.  40 char max.
	'shiptozip' => '', 								// Required if shipping is included.  Postal code of shipping address.  20 char max.
	'shiptocountry' => '', 								// Required if shipping is included.  Country code of shipping address.  2 char max.
	'shiptophonenum' => '',  							// Phone number for shipping address.  20 char max.
	'notetext' => 'This is a test note before ever having left the web site.', 	// Note to the merchant.  255 char max.  
	'allowedpaymentmethod' => '', 							// The payment method type.  Specify the value InstantPaymentOnly.
	'paymentaction' => 'Sale', 							// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
	'paymentrequestid' => '',  							// A unique identifier of the specific payment request, which is required for parallel payments. 
	'sellerpaypalaccountid' => ''							// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
);
				
$PaymentOrderItems = array();
$Item = array(
	'name' => 'Widget 123', 					// Item name. 127 char max.
	'desc' => 'Widget 123', 					// Item description. 127 char max.
	'amt' => '40.00', 						// Cost of item.
	'number' => '123', 						// Item number.  127 char max.
	'qty' => '1', 							// Item qty on order.  Any positive integer.
	'taxamt' => '', 						// Item sales tax
	'itemurl' => 'http://www.angelleye.com/products/123.php', 	// URL for the item.
	'itemweightvalue' => '', 					// The weight value of the item.
	'itemweightunit' => '', 					// The weight unit of the item.
	'itemheightvalue' => '', 					// The height value of the item.
	'itemheightunit' => '', 					// The height unit of the item.
	'itemwidthvalue' => '', 					// The width value of the item.
	'itemwidthunit' => '', 						// The width unit of the item.
	'itemlengthvalue' => '', 					// The length value of the item.
	'itemlengthunit' => '',  					// The length unit of the item.
	'ebayitemnumber' => '', 					// Auction item number.  
	'ebayitemauctiontxnid' => '', 					// Auction transaction ID number.  
	'ebayitemorderid' => '',  					// Auction order ID number.
	'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
);
array_push($PaymentOrderItems, $Item);

$Item = array(
	'name' => 'Widget 456', 					// Item name. 127 char max.
	'desc' => 'Widget 456', 					// Item description. 127 char max.
	'amt' => '40.00', 						// Cost of item.
	'number' => '456', 						// Item number.  127 char max.
	'qty' => '1', 							// Item qty on order.  Any positive integer.
	'taxamt' => '', 						// Item sales tax
	'itemurl' => 'http://www.angelleye.com/products/456.php', 	// URL for the item.
	'itemweightvalue' => '', 					// The weight value of the item.
	'itemweightunit' => '', 					// The weight unit of the item.
	'itemheightvalue' => '', 					// The height value of the item.
	'itemheightunit' => '', 					// The height unit of the item.
	'itemwidthvalue' => '', 					// The width value of the item.
	'itemwidthunit' => '', 						// The width unit of the item.
	'itemlengthvalue' => '', 					// The length value of the item.
	'itemlengthunit' => '',  					// The length unit of the item.
	'ebayitemnumber' => '', 					// Auction item number.  
	'ebayitemauctiontxnid' => '', 					// Auction transaction ID number.  
	'ebayitemorderid' => '',  					// Auction order ID number.
	'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
);
array_push($PaymentOrderItems, $Item);

$Payment['order_items'] = $PaymentOrderItems;
array_push($Payments, $Payment);				

$UserSelectedOptions = array(
	'shippingcalculationmode' => '', 	// Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
	'insuranceoptionselected' => '', 	// The Yes/No option that you chose for insurance.
	'shippingoptionisdefault' => '', 	// Is true if the buyer chose the default shipping option.  
	'shippingoptionamount' => '', 		// The shipping amount that was chosen by the buyer.
	'shippingoptionname' => '', 		// Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
);

$GetExpressCheckoutRequest = array(
	'DECPFields' => $DECPFields, 
	'Payments' => $Payments
);

/*
 * Here we call GetExpressCheckoutDetails to obtain payer information from PayPal
 */
if ( $api_mode === 'rest' ) {
        $GECDResult = $PayPal->GetExpressCheckoutDetails($GetExpressCheckoutRequest);
} else {
        $GECDResult = $PayPal->GetExpressCheckoutDetails($orderId);
}
echo '<b>GetExpressCheckoutDetails</b><br /><pre>';
print_r($GECDResult);
echo '<br /><br /></pre>';

if ( $api_mode === 'rest' && ! $api_upgrade ) {
        $payerID = isset($GECDResult['order']['payer']['payer_id']) ? $GECDResult['order']['payer']['payer_id'] : '';
} else {
        $payerID = $GECDResult['PAYERID'];
}

$DECPFields['payerid'] = $payerID;

$PayPalRequest = array(
	'DECPFields' => $DECPFields, 
	'Payments' => $Payments
);

$_SESSION['PayPalResult'] = $PayPal->DoExpressCheckoutPayment($PayPalRequest);
echo '<b>DoExpressCheckoutPayment</b><br /><pre />';
print_r($_SESSION['PayPalResult']);
