<?php
require_once('../../includes/config.php');
require_once('../../autoload.php');

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature, 
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
	'PayPalAPIUpgrade' => $api_upgrade,
    'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret,
    'MerchantID' => $rest_merchant_id
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$DPFields = array(
	'paymentaction' => 'Sale', 				        // How you want to obtain payment.  Authorization indicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
	'restintent' => 'CAPTURE',				        // REST only.  Payment intent.  Allowed values are:  authorize, order, and capture.  Default is capture.
	'ipaddress' => $_SERVER['REMOTE_ADDR'], 		// Required.  IP address of the payer's browser.
	'returnfmfdetails' => '1' 				        // Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
);
				
$CCDetails = array(
	'creditcardtype' => $_SESSION['card_details']['creditcardtype'], 		// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
	'acct' => $_SESSION['card_details']['accountnumber'], 		            // Required.  Credit card number.  No spaces or punctuation.
	'expdate' => $_SESSION['card_details']['expiry'], 			            // Required.  Credit card expiration date.  Format is MMYYYY
	'cvv2' => $_SESSION['card_details']['cvv'], 			                // Requirements determined by your PayPal account settings.  Security digits for credit card.
	'startdate' => '', 			                                            // Month and year that Maestro or Solo card was issued.  MMYYYY
	'issuenumber' => ''			                                            // Issue number of Maestro or Solo card.  Two numeric digits max.
);
				
$PayerInfo = array(
	'email' => $_SESSION['payer']['email'], 	// Email address of payer.
	'payerid' => '', 					    // Unique PayPal customer ID for payer.
	'payerstatus' => '', 					// Status of payer.  Values are verified or unverified
	'business' => '' 					    // Payer's business name.
);
				
$PayerName = array(
	'salutation' => '', 			                    // Payer's salutation.  20 char max.
	'firstname' => $_SESSION['payer']['firstname'], 	// Payer's first name.  25 char max.
	'middlename' => '', 			                    // Payer's middle name.  25 char max.
	'lastname' => $_SESSION['payer']['lastname'], 		// Payer's last name.  25 char max.
	'suffix' => ''				                        // Payer's suffix.  12 char max.
);
				
$BillingAddress = array(
	'street' => $_SESSION['billing']['street'], 	        // Required.  First street address.
	'street2' => '', 			                            // Second street address.
	'city' => $_SESSION['billing']['city'], 			    // Required.  Name of City.
	'state' => $_SESSION['billing']['state'], 			    // Required. Name of State or Province.
	'countrycode' => $_SESSION['billing']['countrycode'],   // Required.  Country code.
	'zip' => $_SESSION['billing']['zip'], 			        // Required.  Postal code of payer.
	'phonenum' => $_SESSION['payer']['phonenumber'] 		// Phone Number of payer.  20 char max.
);
					
$ShippingAddress = array(
	'shiptoname' => '', 			// Required if shipping is included.  Person's name associated with this address.  32 char max.
	'shiptostreet' => '', 			// Required if shipping is included.  First street address.  100 char max.
	'shiptostreet2' => '', 			// Second street address.  100 char max.
	'shiptocity' => '', 			// Required if shipping is included.  Name of city.  40 char max.
	'shiptostate' => '', 			// Required if shipping is included.  Name of state or province.  40 char max.
	'shiptozip' => '', 			// Required if shipping is included.  Postal code of shipping address.  20 char max.
	'shiptocountrycode' => '', 		// Required if shipping is included.  Country code of shipping address.  2 char max.
	'shiptophonenum' => ''			// Phone number for shipping address.  20 char max.
);
					
$PaymentDetails = array(
	'amt' => $_SESSION['shopping_cart']['grand_total'], // Required.  Total amount of order, including shipping, handling, and tax.  
	'currencycode' => 'USD', 				// Required.  Three-letter currency code.  Default is USD.
	'itemamt' => '', 					// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
	'shippingamt' => number_format($_SESSION['shopping_cart']['shipping'], 2), 					// Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
	'handlingamt' => number_format($_SESSION['shopping_cart']['handling'], 2), 					// Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
	'taxamt' => number_format($_SESSION['shopping_cart']['tax'], 2), 					// Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax. 
	'desc' => 'Testing Payments Pro DESC Field', 		// Description of the order the customer is purchasing.  127 char max.
	'custom' => 'TEST', 					// Free-form field for your own use.  256 char max.
	'invnum' => 'ABC-123-XYZ', 				// Your own invoice or tracking number
	'buttonsource' => '', 					// An ID code for use by 3rd party apps to identify transactions.
	'notifyurl' => ''					// URL for receiving Instant Payment Notifications.  This overrides what your profile is set to use.
);

/**
 * Here we'll begin creating our order items that belong to this $Payment in the request.
 * We will loop through the items in our shopping cart to add them each into our
 * $Payment.
 */
$OrderItems = array();
foreach ($_SESSION['shopping_cart']['acdc_items'] as $cart_item) {
    $Item = array(
        'name' => $cart_item['name'], // Item name. 127 char max.
        'amt' => $cart_item['price'], // Cost of item.
        'number' => $cart_item['id'], // Item number.  127 char max.
        'qty' => $cart_item['qty'], // Item qty on order.  Any positive integer.
    );
    array_push($OrderItems, $Item);
}

$PayPalRequestData = array(
	'DPFields' => $DPFields, 
	'CCDetails' => $CCDetails, 
	'PayerInfo' => $PayerInfo,
	'PayerName' => $PayerName, 
	'BillingAddress' => $BillingAddress, 
	'PaymentDetails' => $PaymentDetails, 
	'OrderItems' => $OrderItems
);

$PayPalResult = $PayPal->DoDirectPayment($PayPalRequestData);

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables.
 */
if( $api_mode === 'classic' && $PayPal->APICallSuccessful($PayPalResult['ACK']) ) {
    $_SESSION['paypal_transaction_id'] = isset($PayPalResult['TRANSACTIONID']) ? $PayPalResult['TRANSACTIONID'] : '';
    header('Location: order-complete.php');
} elseif( $api_mode === 'rest' && ($PayPalResult['SUCCESS'] || ( $api_upgrade && $PayPalResult['ACK'] && strtoupper($PayPalResult['ACK']) === 'SUCCESS' )) ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $_SESSION['paypal_transaction_id'] = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['RESPONSE']['id']) ? $PayPalResult['RESPONSE']['id'] : '' )
                                    : ( isset($PayPalResult['TRANSACTIONID']) ? $PayPalResult['TRANSACTIONID'] : '' );
    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}