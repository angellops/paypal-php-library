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

/*
 * Here we call GetExpressCheckoutDetails to obtain payer information from PayPal
 */
$PayPalResult = $PayPal->getOrder($_GET['order_id']);

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables.
 */
if( $PayPalResult['success'] ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $_SESSION['paypal_payer_id'] = isset($PayPalResult['order']['payer']['payer_id']) ? $PayPalResult['order']['payer']['payer_id'] : '';
    $_SESSION['phone_number']   = isset($PayPalResult['order']['payer']['phone']['phone_number']['national_number']) ? $PayPalResult['order']['payer']['phone']['phone_number']['national_number'] : '';
    $_SESSION['email']          = isset($PayPalResult['order']['payer']['email_address']) ? $PayPalResult['order']['payer']['email_address'] : '';
    $_SESSION['first_name']     = isset($PayPalResult['order']['payer']['name']['given_name']) ? $PayPalResult['order']['payer']['name']['given_name'] : '';
    $_SESSION['last_name']      = isset($PayPalResult['order']['payer']['name']['surname']) ? $PayPalResult['order']['payer']['name']['surname'] : '';
    $_SESSION['billing_country_code'] = isset($PayPalResult['order']['payer']['address']['country_code']) ? $PayPalResult['order']['payer']['address']['country_code'] : '';

    $purchaseUnit = $PayPalResult['order']['purchase_units'][0]; 
    
    $captures = !empty($purchaseUnit['payments']['captures'][0]) ? $purchaseUnit['payments']['captures'][0] : [];
    $_SESSION['paypal_transaction_id'] = isset($captures['id']) ? $captures['id'] : '';
    $_SESSION['paypal_fee'] = isset( $captures['seller_receivable_breakdown']['paypal_fee']['value'] ) ? $captures['seller_receivable_breakdown']['paypal_fee']['value'] : 0.00;
    
    $shipping = isset( $purchaseUnit['shipping'] ) ? $purchaseUnit['shipping'] : [];
    $_SESSION['shipping_name'] = isset($shipping['name']['full_name']) ? $shipping['name']['full_name'] : '';
    $_SESSION['shipping_street'] = isset($shipping['address']['address_line_1']) ? $shipping['address']['address_line_1'] : '';
    $_SESSION['shipping_city'] = isset($shipping['address']['admin_area_2']) ? $shipping['address']['admin_area_2'] : '';
    $_SESSION['shipping_state'] = isset($shipping['address']['admin_area_1']) ? $shipping['address']['admin_area_1'] : '';
    $_SESSION['shipping_zip'] = isset($shipping['address']['postal_code']) ? $shipping['address']['postal_code'] : '';
    $_SESSION['shipping_country_code'] = isset($shipping['address']['country_code']) ? $shipping['address']['country_code'] : '';
    $_SESSION['shipping_country_name'] = 'United States';

    /**
     * At this point, we now have the buyer's shipping address available in our app.
     * We could now run the data through a shipping calculator to retrieve rate
     * information for this particular order.
     *
     * This would also be the time to calculate any sales tax you may need to
     * add to the order, as well as handling fees.
     */
    $breakdown = !empty($purchaseUnit['amount']['breakdown']) ? $purchaseUnit['amount']['breakdown'] : [];
    $shipping = isset($breakdown['shipping']['value']) ? (float)$breakdown['shipping']['value'] : 0;
    $handling = isset($breakdown['handling']['value']) ? (float)$breakdown['handling']['value'] : 0;
    $tax = isset($breakdown['tax_total']['value']) ? (float)$breakdown['tax_total']['value'] : 0; 
    
    // Store in session
    $_SESSION['shopping_cart']['shipping'] = $shipping;
    $_SESSION['shopping_cart']['handling'] = $handling;
    $_SESSION['shopping_cart']['tax'] = $tax;  

    // Recalculate grand total
    $_SESSION['shopping_cart']['grand_total'] = number_format(
        $_SESSION['shopping_cart']['subtotal']
        + $shipping
        + $handling
        + $tax, 2);
    
    /**
     * Now we will redirect the user to a final review
     * page so they can see the shipping/handling/tax
     * that has been added to the order.
     */
    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}