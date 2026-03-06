<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
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
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$paymentMode = isset($_GET['payment_mode']) ? $_GET['payment_mode'] : '';

// Map items from session
$purchase_items = [];
foreach ($_SESSION['checkout_options_items'] as $item) {
    $purchase_items[] = [
        "name" => $item['name'],
        "sku" => $item['id'],
        "unit_amount" => [
            "currency_code" => "USD",
            "value" => number_format($item['price'], 2, '.', '')
        ],
        "quantity" => (string)$item['qty']
    ];
}

// Build the Base Payload
$payload = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "items" => $purchase_items,
        "amount" => [
            "currency_code" => "USD",
            "value" => $_SESSION['shopping_cart']['grand_total'],
            "breakdown" => [
                "item_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($_SESSION['shopping_cart']['subtotal'], 2, '.', '')
                ],
                "shipping" => [
                    "currency_code" => "USD",
                    "value" => number_format($_SESSION['shopping_cart']['shipping'], 2, '.', '')
                ],
                "handling" => [
                    "currency_code" => "USD",
                    "value" => number_format($_SESSION['shopping_cart']['handling'], 2, '.', '')
                ],
                "tax_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($_SESSION['shopping_cart']['tax'], 2, '.', '')
                ]
            ]
        ]
    ]],
    "payment_source" => [
        "paypal" => [
            "experience_context" => [
                "brand_name" => "Angell EYE",
                "shipping_preference" => "GET_FROM_FILE",
                "user_action" => "PAY_NOW",
                "return_url" => $domain . "demo/paypal-multiple-checkout-options/captureOrder.php?payment_mode=" . $paymentMode,
                "cancel_url" => $domain . "demo/paypal-multiple-checkout-options/",
            ],
        ]
	]
];

/**
 * Here we are making the call to the createOrder function in the library,
 * and we're passing in our $PayPalRequestData that we just set above.
 */
$PayPalResult = $PayPal->createOrder($payload);

/**
 * Based on the selected API mode, extract the appropriate redirect URL
 * and order identifier from the createOrder response.
 *
 * - For REST mode (when API upgrade is disabled), PayPal returns an
 *   `approval_url` for redirection and an `order_id` to track the transaction.
 * - For Classic mode (or when API upgrade is enabled), PayPal returns a
 *   `REDIRECTURL` for redirection and a `TOKEN` that represents the checkout session.
 *
 * These values are normalized into $redirect_url and $orderId so the
 * remaining flow can work consistently regardless of API mode.
 */
$redirect_url = '';
if( !$api_upgrade ) {
    $redirect_url = $PayPalResult['approval_url'];
} else {
    $redirect_url = $PayPalResult['REDIRECTURL'];
}

$orderId = '';
if( !$api_upgrade ) {
    $orderId = $PayPalResult['order_id'];
} else {
    $orderId = $PayPalResult['TOKEN'];
}

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an 
 * error page to display it accordingly.
 *
 * If all goes well, we save our token in a session variable so that it's
 * readily available for us later, and then redirect the user to PayPal
 * using the REDIRECTURL returned by the SetExpressCheckout() function.
 */
if( !empty($redirect_url) ) {
    $_SESSION['paypal_token'] = $orderId;
    header('Location: ' . $redirect_url);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}