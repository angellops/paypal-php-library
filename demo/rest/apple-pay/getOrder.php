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
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

$PayPalResult = $PayPal->getOrder($_GET['order_id']);

if ( $PayPalResult['success'] ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $_SESSION['paypal_transaction_id'] = $_GET['order_id'];
    $_SESSION['paypal_payer_id'] = isset($PayPalResult['order']['payer']['payer_id']) ? $PayPalResult['order']['payer']['payer_id'] : '';
    $_SESSION['phone_number']   = isset($PayPalResult['order']['payer']['phone']['phone_number']['national_number']) ? $PayPalResult['order']['payer']['phone']['phone_number']['national_number'] : '';
    $_SESSION['email']          = isset($PayPalResult['order']['payer']['email_address']) ? $PayPalResult['order']['payer']['email_address'] : '';
    $_SESSION['first_name']     = isset($PayPalResult['order']['payer']['name']['given_name']) ? $PayPalResult['order']['payer']['name']['given_name'] : '';
    $_SESSION['last_name']      = isset($PayPalResult['order']['payer']['name']['surname']) ? $PayPalResult['order']['payer']['name']['surname'] : '';
    $_SESSION['billing_country_code'] = isset($PayPalResult['order']['payer']['address']['country_code']) ? $PayPalResult['order']['payer']['address']['country_code'] : '';

    $purchaseUnit = $PayPalResult['order']['purchase_units'][0];
    $shipping = isset( $purchaseUnit['shipping'] ) ? $purchaseUnit['shipping'] : [];
    $_SESSION['shipping_name'] = isset($shipping['name']['full_name']) ? $shipping['name']['full_name'] : '';
    $_SESSION['shipping_street'] = isset($shipping['address']['address_line_1']) ? $shipping['address']['address_line_1'] : '';
    $_SESSION['shipping_city'] = isset($shipping['address']['admin_area_2']) ? $shipping['address']['admin_area_2'] : '';
    $_SESSION['shipping_state'] = isset($shipping['address']['admin_area_1']) ? $shipping['address']['admin_area_1'] : '';
    $_SESSION['shipping_zip'] = isset($shipping['address']['postal_code']) ? $shipping['address']['postal_code'] : '';
    $_SESSION['shipping_country_code'] = isset($shipping['address']['country_code']) ? $shipping['address']['country_code'] : '';
    $_SESSION['shipping_country_name'] = isset($shipping['address']['country_code']) ? $shipping['address']['country_code'] : '';

    /**
     * At this point, we now have the buyer's shipping address available in our app.
     * We could now run the data through a shipping calculator to retrieve rate
     * information for this particular order.
     *
     * This would also be the time to calculate any sales tax you may need to
     * add to the order, as well as handling fees.
     *
     * We're going to set static values for these things in our static
     * shopping cart, and then re-calculate our grand total.
     */
    $_SESSION['shopping_cart']['shipping'] = 10.00;
    $_SESSION['shopping_cart']['handling'] = 2.50;
    $_SESSION['shopping_cart']['tax'] = 1.50;

    $_SESSION['shopping_cart']['grand_total'] = number_format(
        $_SESSION['shopping_cart']['subtotal']
        + $_SESSION['shopping_cart']['shipping']
        + $_SESSION['shopping_cart']['handling']
        + $_SESSION['shopping_cart']['tax'],2);

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