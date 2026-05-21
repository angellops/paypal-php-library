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

$items = [];
foreach ($_SESSION['shopping_cart']['paylater_items'] as $id => $item) {
    $items[] = [
        'name' => $item['name'],
        'quantity' => $item['qty'],
    ];
}

$trackingPayload = [
    'capture_id' => $_SESSION['paypal_transaction_id'],
    'tracking_number' => '443844607820',
    'status' => 'SHIPPED',
    'carrier' => 'OTHER', // Examples: FEDEX, DHL, USPS, UPS
    'carrier_name_other' => 'In-Store Pickup / Local Delivery', // Use if carrier is 'OTHER'
    'notify_payer' => false,
    'items' => $items
];

$PayPalTrackData = [
    'orderID' => $_SESSION['paypal_token'],
    'trackingPayload' => $trackingPayload
];

$PayPalResult = $PayPal->trackOrder($PayPalTrackData);

// Store Debug IDs
if (!empty($PayPalResult['debug_id'])) {
    $_SESSION['paypal_debug_ids'][] = [
        'action'   => 'trackOrder',
        'debug_id' => $PayPalResult['debug_id'],
        'time'     => date('H:i:s'),
    ];
}

if( $PayPalResult['success'] ) {
    $_SESSION['paypal_tracking_status'] = $PayPalResult['tracking_status'];
    $_SESSION['paypal_tracking_ids'] = $PayPalResult['tracking_ids'];
    header('Location: order-tracking.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}
