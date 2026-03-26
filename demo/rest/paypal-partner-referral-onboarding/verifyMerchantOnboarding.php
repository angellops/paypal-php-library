<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
    'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id_2,
	'ClientSecret' => $rest_client_secret_2,
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Exit early if the 'onboarding' parameter is not present in the URL
if( !$_GET['onbaording'] ) {
    return;
}

// Extract Merchant IDs from the GET request
// The internal ID used to track this specific onboarding session
$trackingId = isset( $_GET['merchantId'] ) ? $_GET['merchantId'] : '';

// The actual PayPal Merchant ID returned by the API
$merchantId = isset( $_GET['merchantIdInPayPal'] ) ? $_GET['merchantIdInPayPal'] : '';

// Call the PayPal service to verify the onboarding status
$PayPalResult = $PayPal->verifyMerchantOnboarding($merchantId);

// Handle the verification outcome
if( $PayPalResult['success'] ) {
    $_SESSION['verified_merchant_data'] = $PayPalResult['full_response'];
    header('Location: merchant-onboarded.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}