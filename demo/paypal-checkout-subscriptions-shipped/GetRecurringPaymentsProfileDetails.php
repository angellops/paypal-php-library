<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
}

// Create PayPal object.
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

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$PayPalRequestData = array(
	'subscription_id' => isset($_GET['subscription_id']) ? $_GET['subscription_id'] : '',
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->GetSubscriptionProfile($PayPalRequestData);

if( $PayPalResult['success'] ) {
        $full_response = ( !$api_upgrade && isset( $PayPalResult['full_response'] ) ) ? $PayPalResult['full_response'] : [];
        $_SESSION['RecurringProfileId'] = ( !empty( $full_response ) && isset( $full_response['id'] ) ) ? $full_response['id'] : '';

        header('Location: order-complete.php');
} else {
        $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
        header('Location: ../error.php');
}