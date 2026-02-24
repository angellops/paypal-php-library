<?php

/**
 * Include our config file and the PayPal library.
 */
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
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


$DECPFields = array(
    'token' => $_SESSION['paypal_token'], 								// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
    'payerid' => '', 							                        // Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
);

/**
 * Just like with SetExpressCheckout, we need to gather our $Payment
 * data to pass into our $Payments array.  This time we can include
 * the shipping, handling, tax, and shipping address details that we
 * now have.
 */
$Payments = array();
$Payment = array(
    'amt' => number_format($_SESSION['shopping_cart']['grand_total'],2), 	    // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
    'itemamt' => number_format($_SESSION['shopping_cart']['subtotal'],2),       // Subtotal of items only.
);

/**
 * Here we'll begin creating our order items that belong to this $Payment in the request.
 * We will loop through the items in our shopping cart to add them each into our
 * $Payment.
 */
$PaymentOrderItems = array();
foreach ($_SESSION['shopping_cart']['items'] as $cart_item) {
    $Item = array(
        'name' => $cart_item['name'], // Item name. 127 char max.
        'amt' => $cart_item['price'], // Cost of item.
        'number' => $cart_item['id'], // Item number.  127 char max.
        'qty' => $cart_item['qty'], // Item qty on order.  Any positive integer.
        'itemcategory' => $cart_item['category'], // Item category.  127 char max.
    );
    array_push($PaymentOrderItems, $Item);
}

/**
 * Now that $PaymentOrderItems is filled with all of our shopping cart items,
 * we'll add that to our $Payment array.
 */
$Payment['order_items'] = $PaymentOrderItems;

/**
 * Here we push our single $Payment into our $Payments array.
 */
array_push($Payments, $Payment);

/**
 * Now we gather all of the arrays above into a single array.
 */
$PayPalRequestData = array(
    'DECPFields' => $DECPFields, 
    'Payments' => $Payments, 
);

/*
 * Here we call GetExpressCheckoutDetails to obtain payer information from PayPal
 */
if ( $api_mode === 'rest' ) {
    $PayPalResult = $PayPal->GetExpressCheckoutDetails($PayPalRequestData);
} else {
    $PayPalResult = $PayPal->GetExpressCheckoutDetails($_SESSION['paypal_token']);
}

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables.
 */
if( $api_mode === 'classic' && $PayPal->APICallSuccessful($PayPalResult['ACK']) ) {
    /**
     * Here we'll pull out data from the PayPal response.
     * Refer to the PayPal API Reference for all of the variables available
     * in $PayPalResult['variablename']
     *
     * https://developer.paypal.com/docs/classic/api/merchant/GetExpressCheckoutDetails_API_Operation_NVP/
     *
     * Again, Express Checkout allows for parallel payments, so what we're doing here
     * is usually the library to parse out the individual payments using the GetPayments()
     * method so that we can easily access the data.
     *
     * We only have a single payment here, which will be the case with most checkouts,
     * but we will still loop through the $Payments array returned by the library
     * to grab our data accordingly.
     */
    $_SESSION['paypal_payer_id'] = isset($PayPalResult['PAYERID']) ? $PayPalResult['PAYERID'] : '';
    $_SESSION['phone_number'] = isset($PayPalResult['PHONENUM']) ? $PayPalResult['PHONENUM'] : '';
    $_SESSION['email'] = isset($PayPalResult['EMAIL']) ? $PayPalResult['EMAIL'] : '';
    $_SESSION['first_name'] = isset($PayPalResult['FIRSTNAME']) ? $PayPalResult['FIRSTNAME'] : '';
    $_SESSION['last_name'] = isset($PayPalResult['LASTNAME']) ? $PayPalResult['LASTNAME'] : '';

    /**
     * Now we will redirect the user to a final review
     * page so they can see the shipping/handling/tax
     * that has been added to the order.
     */
    header('Location: DoExpressCheckoutPayment.php');
} elseif( $api_mode === 'rest' && ($PayPalResult['success'] || ( $api_upgrade && $PayPalResult['ACK'] && strtoupper($PayPalResult['ACK']) === 'SUCCESS' )) ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $_SESSION['paypal_payer_id'] = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['payer_id']) ? $PayPalResult['order']['payer']['payer_id'] : '' )
                                    : ( isset($PayPalResult['PAYERID']) ? $PayPalResult['PAYERID'] : '' );
    $_SESSION['phone_number']   = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['phone']['phone_number']['national_number']) ? $PayPalResult['order']['payer']['phone']['phone_number']['national_number'] : '' )
                                    : ( isset($PayPalResult['PHONENUM']) ? $PayPalResult['PHONENUM'] : '' );
    $_SESSION['email']          = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['email_address']) ? $PayPalResult['order']['payer']['email_address'] : '' )
                                    : ( isset($PayPalResult['EMAIL']) ? $PayPalResult['EMAIL'] : '' );
    $_SESSION['first_name']     = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['name']['given_name']) ? $PayPalResult['order']['payer']['name']['given_name'] : '' )
                                    : ( isset($PayPalResult['FIRSTNAME']) ? $PayPalResult['FIRSTNAME'] : '' );
    $_SESSION['last_name']      = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['name']['surname']) ? $PayPalResult['order']['payer']['name']['surname'] : '' )
                                    : ( isset($PayPalResult['LASTNAME']) ? $PayPalResult['LASTNAME'] : '' );
    $_SESSION['billing_country_code'] = ( ! $api_upgrade ) 
                                    ? ( isset($PayPalResult['order']['payer']['address']['country_code']) ? $PayPalResult['order']['payer']['address']['country_code'] : '' )
                                    : ( isset($PayPalResult['COUNTRYCODE']) ? $PayPalResult['COUNTRYCODE'] : '' );

    /**
     * Now we will redirect the user to a final review
     * page so they can see the shipping/handling/tax
     * that has been added to the order.
     */
    header('Location: DoExpressCheckoutPayment.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}