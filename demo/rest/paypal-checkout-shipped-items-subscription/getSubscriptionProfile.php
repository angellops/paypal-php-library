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
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Get subscription ID
$subscriptionID = isset($_GET['subscription_id']) ? $_GET['subscription_id'] : '';

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->getSubscriptionProfile($subscriptionID);

if( $PayPalResult['success'] ) {
    $full_response = !empty( $PayPalResult['full_response'] ) ? $PayPalResult['full_response'] : [];
    $_SESSION['recurring_profile_id'] = !empty( $full_response ) && isset( $full_response['id'] ) ? $full_response['id'] : '';

    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}