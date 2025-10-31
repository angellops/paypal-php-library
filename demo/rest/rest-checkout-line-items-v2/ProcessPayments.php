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

$paymentIntent = isset($_SESSION['payment_intent']) ? $_SESSION['payment_intent'] : 'CAPTURE';

$orderId = isset($_GET['token']) ? $_GET['token'] : '';

if ($paymentIntent === 'CAPTURE') {
    // Capture order
    $PayPalResult = $PayPal->captureOrder($orderId);

    if ($PayPalResult['success']) {
        $_SESSION['paypal_transaction_id'] = $PayPalResult['capture_id'] ?? '';
        
        $captures = $PayPalResult['purchase_units'][0]['payments']['captures'][0] ?? [];
        $_SESSION['paypal_fee'] = $captures['seller_receivable_breakdown']['paypal_fee']['value'] ?? 0.00;

        header('Location: order-complete.php');
        exit;
    }

} else {
    // Authorize order
    $PayPalResult = $PayPal->authorizeOrder($orderId);

    if ($PayPalResult['success']) {
        $_SESSION['authorization_id'] = $PayPalResult['authorization_id'] ?? '';

        header('Location: CapturePayment.php');
        exit;
    }
}

// If there’s an error in either case
$_SESSION['paypal_errors'] = $PayPalResult['error'] ?? 'Unknown error';
header('Location: ../../error.php');
exit;
