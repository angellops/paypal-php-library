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

// Store Debug IDs
if (!empty($PayPalResult['debug_id'])) {
    $_SESSION['paypal_debug_ids'][] = [
        'action'   => 'getOrder',
        'debug_id' => $PayPalResult['debug_id'],
        'time'     => date('H:i:s'),
    ];
}

if ( $PayPalResult['success'] ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $_SESSION['paypal_payer_id'] = isset($PayPalResult['order']['payer']['payer_id']) ? $PayPalResult['order']['payer']['payer_id'] : '';
    
    $card_info = isset($PayPalResult['order']['payment_source']['google_pay']['card']) ? $PayPalResult['order']['payment_source']['google_pay']['card'] : [];
    $_SESSION['card_brand'] = isset($card_info['brand']) ? $card_info['brand'] : '';
    $_SESSION['card_last_digits'] = isset($card_info['last_digits']) ? $card_info['last_digits'] : '';
    $_SESSION['billing_name'] = isset($card_info['name']) ? $card_info['name'] : '';
    $_SESSION['billing_street'] = isset($card_info['billing_address']['address_line_1']) ? $card_info['billing_address']['address_line_1'] : '';
    $_SESSION['billing_city'] = isset($card_info['billing_address']['admin_area_2']) ? $card_info['billing_address']['admin_area_2'] : '';
    $_SESSION['billing_state'] = isset($card_info['billing_address']['admin_area_1']) ? $card_info['billing_address']['admin_area_1'] : '';
    $_SESSION['billing_zip'] = isset($card_info['billing_address']['postal_code']) ? $card_info['billing_address']['postal_code'] : '';
    $_SESSION['billing_country_code'] = isset($card_info['billing_address']['country_code']) ? $card_info['billing_address']['country_code'] : 'US';

    // Initialize temporary arrays to collect data
    $transaction_ids = [];
    $paypal_fees = [];
    if( isset( $PayPalResult['order']['purchase_units'] ) ) {
        foreach( $PayPalResult['order']['purchase_units'] as $unit ) {
            if( isset( $unit['payments']['captures'][0] ) ) {
                $capture = $unit['payments']['captures'][0];
                $transaction_ids[] = $capture['id'];
                $paypal_fees[] = isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) 
                                ? $capture['seller_receivable_breakdown']['paypal_fee']['value'] 
                                : '0.00';
            }
        }
    }

    // If only 1 item, store as string. If multiple, store as array.
    $_SESSION['paypal_transaction_id'] = (count($transaction_ids) === 1) ? $transaction_ids[0] : $transaction_ids;
    $_SESSION['paypal_fee']            = (count($paypal_fees) === 1) ? $paypal_fees[0] : $paypal_fees;

    $purchaseUnit = $PayPalResult['order']['purchase_units'][0];

    // Payee Email Address
    $_SESSION['email'] = isset($purchaseUnit['payee']['email_address']) ? $purchaseUnit['payee']['email_address'] : '';

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