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
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

$patchData = [
    [
        'op'    => 'replace',
        'path'  => "/purchase_units/@reference_id=='" . $_SESSION['reference_id'] . "'/shipping/name",
        'value' => [
            'full_name' => $_POST['first_name'] . ' ' . $_POST['last_name']
        ]
    ]
];

$PayPalPatchData = [
    'orderID' => $_SESSION['paypal_token'],
    'patchData' => $patchData
];

$PayPalResult = $PayPal->patchOrder($PayPalPatchData);

if( $PayPalResult['success'] ) {
    $_SESSION['shipping_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
    header('Location: captureOrder.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}
