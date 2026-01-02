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
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

/*
 * Here we call GetExpressCheckoutDetails to obtain payer information from PayPal
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
if( $PayPal->APICallSuccessful($PayPalResult['ACK']) ) {
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
     * Now we will send the application flow direction to CreateRecurringPaymentsProfile
     * because there is no need for a final review page.
     */
    header('Location: CreateRecurringPaymentsProfile.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}