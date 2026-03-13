<?php
// Include required library files.
require_once('../../../../includes/config.php');
require_once('../../../../autoload.php');

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

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'ae_client_token':
        $token = $PayPal->fetchAccessToken(true);
        echo json_encode(['token' => $token]);
        break;

    case 'ae_create_order':
        $order_response = $PayPal->createOrder($input);
        echo json_encode($order_response);
        break;

    case 'ae_capture_order':
        $capture_response = $PayPal->captureOrder($input['id']);
        echo json_encode($capture_response);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}