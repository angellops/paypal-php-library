<?php 
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../autoload.php');

/**
 * Reads and returns the raw SVG content from a given file path.
 * * Useful for embedding SVG code directly into HTML to allow for 
 * CSS manipulation and reducing HTTP requests.
 *
 * @param string $path The absolute or relative server path to the SVG file.
 * @return string Returns the SVG XML content if found, or an empty string if the file does not exist.
 */
function inline_svg($path) {
  if (file_exists($path)) {
    return file_get_contents($path);
  }
  return '';
}

if( $api_mode === 'rest' ) {
  // Check if vaulting is enabled
  $vaulting = isset($_GET['vaulting']) ? $_GET['vaulting'] : false;
  
  // Create PayPal object.
  $PayPalConfig = array(
    'Sandbox' => $sandbox,
    'PayPalAPIMode' => $api_mode,
    'PayPalAPIUpgrade' => $api_upgrade,
    'ClientID' => ($vaulting) ? $rest_client_id_2 : $rest_client_id,
    'ClientSecret' => ($vaulting) ? $rest_client_secret_2 : $rest_client_secret,
    'MerchantID' => $rest_merchant_id,
    'PrintHeaders' => $print_headers,
    'LogResults' => $log_results,
    'LogPath' => $log_path,
  );
  $PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);
  
  // Get input from request
  $input = json_decode(file_get_contents('php://input'), true);
  $action = isset($_GET['action']) ? $_GET['action'] : '';

  if( !empty( $action ) ) {
    switch( $action ) {
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

      // case 'ae_create_vault_setup_token':
      //   $create_vault_setuptoken_response = $PayPal->createVaultSetupToken($input);
      //   echo json_encode($create_vault_setuptoken_response);
      //   break;

      // case 'ae_get_vault_setup_token':
      //   $get_vault_setuptoken_response = $PayPal->getVaultSetupTokenDetails($input['id']);
      //   echo json_encode($get_vault_setuptoken_response);
      //   break;

      // case 'ae_create_vault_payment_token':
      //   $vaultPaymentData = [
      //       'vault_payment_data' => $input,
      //   ];
      //   $create_vault_paymenttoken_response = $PayPal->createVaultPaymentToken($vaultPaymentData);
      //   echo json_encode($create_vault_paymenttoken_response);
      //   break;

      default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
  }
}