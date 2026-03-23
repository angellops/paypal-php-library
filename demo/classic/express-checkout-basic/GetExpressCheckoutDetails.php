<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

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

$PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

/**
 * Now we pass the PayPal token that we saved to a session variable
 * in the SetExpressCheckout.php file into the GetExpressCheckoutDetails
 * request.
 */
$PayPalResult = $PayPal->GetExpressCheckoutDetails($_SESSION['paypal_token']);

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables.
 */
if($PayPal->APICallSuccessful($PayPalResult['ACK']))
{
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

    $payments = ( $api_mode === 'classic' && $api_upgrade === false ) ? $PayPal->GetPayments($PayPalResult) : $PayPalResult['PAYMENTS'];

    foreach($payments as $payment)
    {
        $_SESSION['shipping_name'] = isset($payment['SHIPTONAME']) ? $payment['SHIPTONAME'] : '';
        $_SESSION['shipping_street'] = isset($payment['SHIPTOSTREET']) ? $payment['SHIPTOSTREET'] : '';
        $_SESSION['shipping_city'] = isset($payment['SHIPTOCITY']) ? $payment['SHIPTOCITY'] : '';
        $_SESSION['shipping_state'] = isset($payment['SHIPTOSTATE']) ? $payment['SHIPTOSTATE'] : '';
        $_SESSION['shipping_zip'] = isset($payment['SHIPTOZIP']) ? $payment['SHIPTOZIP'] : '';
        $_SESSION['shipping_country_code'] = isset($payment['SHIPTOCOUNTRYCODE']) ? $payment['SHIPTOCOUNTRYCODE'] : '';
        $_SESSION['shipping_country_name'] = isset($payment['SHIPTOCOUNTRYNAME']) ? $payment['SHIPTOCOUNTRYNAME'] : '';   
    }

    /**
     * At this point, we now have the buyer's shipping address available in our app.
     * We could now run the data through a shipping calculator to retrieve rate
     * information for this particular order.
     *
     * This would also be the time to calculate any sales tax you may need to
     * add to the order, as well as handling fees.
     *
     * We're going to set static values for these things in our static
     * shopping cart, and then re-calculate our grand total.
     */
    $_SESSION['shopping_cart']['shipping'] = 10.00;
    $_SESSION['shopping_cart']['handling'] = 2.50;
    $_SESSION['shopping_cart']['tax'] = 1.50;

    $_SESSION['shopping_cart']['grand_total'] = number_format(
        $_SESSION['shopping_cart']['subtotal']
        + $_SESSION['shopping_cart']['shipping']
        + $_SESSION['shopping_cart']['handling']
        + $_SESSION['shopping_cart']['tax'],2);

    /**
     * Now we will redirect the user to a final review
     * page so they can see the shipping/handling/tax
     * that has been added to the order.
     */
    header('Location: review.php');
}
else
{
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../../error.php');
}