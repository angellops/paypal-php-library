<?php
// Load dependencies and configuration
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
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

$PayPalResult = $PayPal->captureOrder($_SESSION['paypal_token']);

// Store Debug IDs
if (!empty($PayPalResult['debug_id'])) {
    $_SESSION['paypal_debug_ids'][] = [
        'action'   => 'captureOrder',
        'debug_id' => $PayPalResult['debug_id'],
        'time'     => date('H:i:s'),
    ];
}

if ( $PayPalResult['success'] ) {
    // Initialize temporary arrays to collect data
    $transaction_ids = [];
    $paypal_fees = [];

    // Loop through each purchase unit
    if( isset( $PayPalResult['full_response']['purchase_units'] ) ) {
        foreach( $PayPalResult['full_response']['purchase_units'] as $unit ) {
            if( isset( $unit['payments']['captures'][0] ) ) {
                $capture = $unit['payments']['captures'][0];
                
                // Collect Transaction ID
                $transaction_ids[] = $capture['id'];
                
                // Collect Fee (default to 0.00 if not found)
                $paypal_fees[] = isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) 
                                ? $capture['seller_receivable_breakdown']['paypal_fee']['value'] 
                                : '0.00';
            }
        }
    }

    // Logic: If only 1 item, store as string. If multiple, store as array.
    $_SESSION['paypal_transaction_id'] = (count($transaction_ids) === 1) ? $transaction_ids[0] : $transaction_ids;
    $_SESSION['paypal_fee']            = (count($paypal_fees) === 1) ? $paypal_fees[0] : $paypal_fees;
    
    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}