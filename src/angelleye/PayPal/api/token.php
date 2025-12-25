<?php
// Include required library files.
require_once('../../../../includes/config.php');
require_once('../../../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
        'Sandbox' => $sandbox,
        'PayPalAPIMode' => $api_mode,
        'ClientID' => $rest_client_id,
        'ClientSecret' => $rest_client_secret,
        'PrintHeaders' => $print_headers,
        'LogResults' => $log_results,
        'LogPath' => $log_path,
);

$paypalRest = new angelleye\PayPal\PayPalREST($PayPalConfig);
$token = $paypalRest->fetchAccessToken(true);

echo json_encode(['token' => $token]);