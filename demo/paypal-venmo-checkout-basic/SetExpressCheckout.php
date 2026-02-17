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
        'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Determine payment type
$paymentType = ( !empty($_GET) && !empty($_GET['paywith']) && $_GET['paywith'] === 'venmo' ) ? 'venmo' : 'paypal';

// Map items from session
$purchase_items = [];
foreach ($_SESSION['items'] as $item) {
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
                ]
            ]
        ]
    ]]
];

if ($paymentType === 'venmo') {
	$payload['payment_source'] = [
		'venmo' => [
			'experience_context' => [
				'brand_name' => 'Angell EYE',
				'shipping_preference' => 'GET_FROM_FILE',
			]
		]
	];
} else {
	$payload['payment_source'] = [
		'paypal' => [
			'experience_context' => [
				'brand_name' => 'Angell EYE',
				'shipping_preference' => 'GET_FROM_FILE',
				'return_url' => $domain . 'demo/paypal-venmo-checkout-basic/GetExpressCheckoutDetails.php',
				'cancel_url' => $domain . 'demo/paypal-venmo-checkout-basic/',
			],

			'order_update_callback_config' => [
				'callback_url' => $domain . 'demo/paypal-venmo-checkout-basic/shippingCallback.php',
				'callback_events' => [
					'SHIPPING_ADDRESS',
					'SHIPPING_OPTIONS'
				]
			]
		]
	];

	$payload['payment_method'] = [
		"payer_selected" => "PAYPAL",
		"payee_preferred" => "IMMEDIATE_PAYMENT_REQUIRED"
	];

	$payload['payer'] = [
		"email_address" => $_SESSION['buyer_email']
	];
}

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
} elseif( $PaymentType === 'venmo' ) {
	header('Content-Type: application/json');

	echo json_encode([
		'id' => $orderId,
		'status' => 'success'
	]);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}