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

// Format the amount
$amount = number_format($_SESSION['amount'], 2, '.', '');

// Build the Base Payload
$payload = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "description" => "Charity Donation",
        "custom_id" => json_encode([
            "name" => $_SESSION['name'],
            "email" => $_SESSION['email']
        ]),
        "items" => [[
            "name" => "Donation",
            "description" => "Support our cause",
            "quantity" => "1",
            "unit_amount" => [
                "currency_code" => "USD",
                "value" => $amount
            ],
            "category" => "DONATION"
        ]],
        "amount" => [
            "currency_code" => "USD",
            "value" => $amount,
            "breakdown" => [
                "item_total" => [
                    "currency_code" => "USD",
                    "value" => $amount
                ]
            ]
        ]
    ]],
    "payment_source" => [
        "paypal" => [
            "experience_context" => [
                "brand_name" => "Angell EYE",
                "shipping_preference" => "NO_SHIPPING",
                "return_url" => $domain . "demo/paypal-checkout-donation/getOrder.php",
                "cancel_url" => $domain . "demo/paypal-checkout-donation/",
                "user_action" => "PAY_NOW",
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
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an 
 * error page to display it accordingly.
 *
 * If all goes well, we save our token in a session variable so that it's
 * readily available for us later, and then redirect the user to PayPal
 * using the REDIRECTURL returned by the SetExpressCheckout() function.
 */
if( $PayPalResult['success'] ) {
    $_SESSION['paypal_token'] = $PayPalResult['order_id'];
    header('Location: ' . $PayPalResult['approval_url']);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}