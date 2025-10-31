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
    'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
    'PrintHeaders' => $print_headers,
    'LogResults' => $log_results,
    'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$orderId = isset($_GET['token']) ? $_GET['token'] : '';

$PayPalResult = $PayPal->captureOrder($orderId);

if ($PayPalResult['success']) {
    $_SESSION['paypal_transaction_id'] = isset($PayPalResult['capture_id']) ? $PayPalResult['capture_id'] : '';

    // Get payment info
    $payment_captures = isset($PayPalResult['purchase_units'][0]['payments']['captures']) ? $PayPalResult['purchase_units'][0]['payments']['captures'] : [];
    $_SESSION['paypal_fee'] = isset($payment_captures[0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $payment_captures[0]['seller_receivable_breakdown']['paypal_fee']['value'] : 0.00;

    header('Location: order-complete.php');
    exit;
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['error'];
    header('Location: ../../error.php');
}