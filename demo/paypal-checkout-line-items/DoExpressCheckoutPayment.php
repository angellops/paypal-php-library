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

/**
 * Now we'll setup the request params for the final call in the Express Checkout flow.
 * This is very similar to SetExpressCheckout except that now we can include values
 * for the shipping, handling, and tax amounts, as well as the buyer's name and
 * shipping address that we obtained in the GetExpressCheckoutDetails step.
 *
 * If this information is not included in this final call, it will not be
 * available in PayPal's transaction details data.
 *
 * Once again, the template for DoExpressCheckoutPayment provides
 * many more params that are available, but we've stripped everything
 * we are not using in this basic demo out.
 */
$DECPFields = array(
    'token' => $_SESSION['paypal_token'], 								// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
    'payerid' => $_SESSION['paypal_payer_id'], 							// Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
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
foreach ($_SESSION['shopping_cart']['line_items'] as $cart_item) {
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

/**
 * Here we are making the call to the DoExpressCheckoutPayment function in the library,
 * and we're passing in our $PayPalRequestData that we just set above.
 */
$PayPalResult = $PayPal->DoExpressCheckoutPayment($PayPalRequestData);

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables, and then redirect to our final
 * thank you / receipt page.
 */
if( $api_mode === 'classic' && $PayPal->APICallSuccessful($PayPalResult['ACK']) ) {
    /**
     * Once again, since Express Checkout allows for multiple payments in a single transaction,
     * the DoExpressCheckoutPayment response is setup to provide data for each potential payment.
     * As such, we need to loop through all the payment info in the response.
     *
     * The library helps us do this using the GetExpressCheckoutPaymentInfo() method.  We'll
     * load our $payments_info using that method, and then loop through the results to pull
     * out our details for the transaction.
     *
     * Again, in this case we are you only working with a single payment, but we'll still
     * loop through the results accordingly.
     *
     * Here, we're only pulling out the PayPal transaction ID and fee amount, but you may
     * refer to the API reference for all the additional parameters you have available at
     * this point.
     *
     * https://developer.paypal.com/docs/classic/api/merchant/DoExpressCheckoutPayment_API_Operation_NVP/
     */
    $payments_info = $PayPal->GetExpressCheckoutPaymentInfo($PayPalResult);

    foreach($payments_info as $payment_info)
    {
        $_SESSION['paypal_transaction_id'] = isset($payment_info['TRANSACTIONID']) ? $payment_info['TRANSACTIONID'] : '';
        $_SESSION['paypal_fee'] = isset($payment_info['FEEAMT']) ? $payment_info['FEEAMT'] : '';
    }

    header('Location: order-complete.php');
} elseif( $api_mode === 'rest' && $PayPalResult['success'] ) {
    $_SESSION['paypal_transaction_id'] = isset( $PayPalResult['capture_id'] ) ? $PayPalResult['capture_id'] : '';

    $captures = ( $PayPalResult['purchase_units'][0]['payments']['captures'][0] ) ? $PayPalResult['purchase_units'][0]['payments']['captures'][0] : [];
    $_SESSION['paypal_fee'] = isset( $captures['seller_receivable_breakdown']['paypal_fee']['value'] ) ? $captures['seller_receivable_breakdown']['paypal_fee']['value'] : 0.00;
    
    header('Location: order-complete.php');
} elseif( $api_mode === 'rest' && $api_upgrade && $PayPalResult['ACK'] && strtoupper($PayPalResult['ACK']) === 'SUCCESS' ) {
    $payments_info = isset( $PayPalResult['PAYMENTS'][0] ) ? $PayPalResult['PAYMENTS'][0] : [];

    $_SESSION['paypal_transaction_id'] = isset($payments_info['TRANSACTIONID']) ? $payments_info['TRANSACTIONID'] : '';
    $_SESSION['paypal_fee'] = isset($payments_info['FEEAMT']) ? $payments_info['FEEAMT'] : '0.00';
    
    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}