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
 * Here we are setting up the parameters for a basic Express Checkout flow.
 *
 * The template provided at ../../vendor/angelleye/paypal-php-library/templates/SetExpressCheckout.php
 * contains a lot more parameters that we aren't using here, so I've removed them to keep this clean.
 *
 * $domain used here is set in the config file.
 */
$SECFields = array(
    'maxamt' => number_format($_SESSION['shopping_cart']['grand_total'] * 2,2), 					// The expected maximum total amount the order will be, including S&H and sales tax.
    'returnurl' => $domain . 'demo/paypal-checkout-multiparty/GetExpressCheckoutDetails.php', 							    // Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
    'cancelurl' => $domain . 'demo/paypal-checkout-multiparty/', 							    // Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
    'hdrimg' => 'https://www.angelleye.com/images/angelleye-paypal-header-750x90.jpg', 			// URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
    'logoimg' => 'https://www.angelleye.com/images/angelleye-logo-190x60.jpg', 					// A URL to your logo image.  Formats:  .gif, .jpg, .png.  190x60.  PayPal places your logo image at the top of the cart review area.  This logo needs to be stored on a https:// server.
    'brandname' => 'Angell EYE'							                                // A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
);

// Buyer email fetched from session
$PayerData = array(
	'buyeremail' => $_SESSION['buyer_email']							// buyer email address
);

/**
 * Now we begin setting up our payment(s).
 *
 * Express Checkout includes the ability to setup parallel payments,
 * so we have to populate our $Payments array here accordingly.
 *
 * For this sample we are setting up a two-way split between two
 * sellers, so we will populate two separate $Payment arrays that
 * get pushed into a single $Payments array.
 *
 * Once again, the template file includes a lot more available parameters,
 * but for this basic sample we've removed everything that we're not using,
 * so all we have is an amount.
 */
$Payments = array();
$Payment = array(
    'amt' => $_SESSION['items'][0]['price'], 	// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
    'currencycode' => 'USD',
    'itemamt' => $_SESSION['items'][0]['price'],       // Subtotal of items only.			                                // A three-character currency code.  Default is USD.
    'shippingamt' => 0, 	// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
    'handlingamt' => 0, 	// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
    'taxamt' => 0, 			// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
    'paymentaction' => 'Sale',
    'sellerpaypalaccountid' => $_SESSION['items'][0]['seller_id'],			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
    'paymentrequestid' => 'CART26488-PAYMENT0'
);
/**
 * Here we push our first item $Payment into our $Payments array for seller_a.
 */
array_push($Payments, $Payment);


$Payment = array(
    'amt' => $_SESSION['items'][1]['price'],    // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
    'currencycode' => 'USD',
    'itemamt' => $_SESSION['items'][1]['price'],       // Subtotal of items only.                                            // A three-character currency code.  Default is USD.
    'shippingamt' => 0,     // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
    'handlingamt' => 0,     // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
    'taxamt' => 0,          // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
    'paymentaction' => 'Sale',
    'sellerpaypalaccountid' => $_SESSION['items'][1]['seller_id'],         // A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
    'paymentrequestid' => 'CART26488-PAYMENT1'
);
/**
 * Here we push our second item $Payment into our $Payments array for seller_b.
 */
array_push($Payments, $Payment);

/**
 * Now we gather all of the arrays above into a single array.
 */
$PayPalRequestData = array(
    'SECFields' => $SECFields, 
    'Payments' => $Payments,
	'PayerData' => $PayerData,
);

/**
 * Here we are making the call to the SetExpressCheckout function in the library,
 * and we're passing in our $PayPalRequestData that we just set above.
 */
$PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);

/**
 * Based on the selected API mode, extract the appropriate redirect URL
 * and order identifier from the SetExpressCheckout response.
 *
 * - For REST mode (when API upgrade is disabled), PayPal returns an
 *   `approval_url` for redirection and an `order_id` to track the transaction.
 * - For Classic mode (or when API upgrade is enabled), PayPal returns a
 *   `REDIRECTURL` for redirection and a `TOKEN` that represents the checkout session.
 *
 * These values are normalized into $redirect_url and $orderId so the
 * remaining flow can work consistently regardless of API mode.
 */
$redirect_url = '';
if( $api_mode === 'rest' && !$api_upgrade ) {
    $redirect_url = $PayPalResult['approval_url'];
} else {
    $redirect_url = $PayPalResult['REDIRECTURL'];
}

$orderId = '';
if( $api_mode === 'rest' && !$api_upgrade ) {
    $orderId = $PayPalResult['order_id'];
} else {
    $orderId = $PayPalResult['TOKEN'];
}

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an 
 * error page to display it accordingly.
 *
 * If all goes well, we save our token in a session variable so that it's
 * readily available for us later, and then redirect the user to PayPal
 * using the REDIRECTURL returned by the SetExpressCheckout() function.
 */
if($redirect_url) {
    $_SESSION['paypal_token'] = $orderId;
    header('Location: ' . $redirect_url);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}