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

$ProductData = array(
    "name" => $_SESSION['subscription_name'],
    "description" => $_SESSION['subscription_name'],
    "type" => "SERVICE",
    "category" => "SOFTWARE",
);

$PlanData = array(
    "product_id" => '',
    "name" => "Monthly Subscription Plan",
    "description" => "Monthly recurring subscription with one-time setup fee",
    "billing_cycles" => array(
        array(
            "frequency" => array(
                "interval_unit" => strtoupper($_SESSION['billingperiod']),
                "interval_count" => $_SESSION['billingfrequency']
            ),
            "tenure_type" => "REGULAR",
            "sequence" => 1,
            "total_cycles" => $_SESSION['totalbillingcycles'], // 0 = infinite
            "pricing_scheme" => array(
                "fixed_price" => array(
                    "value" => $_SESSION['shopping_cart']['recurring_items'][1]['amt'],  // monthly charge
                    "currency_code" => "USD"
                )
            )
        )
    ),
    "payment_preferences" => array(
        "auto_bill_outstanding" => true,
        "setup_fee" => array(
            "value" => $_SESSION['shopping_cart']['recurring_items'][0]['amt'], // one-time setup fee
            "currency_code" => "USD"
        ),
        "setup_fee_failure_action" => "CONTINUE",
        "payment_failure_threshold" => 3
    )
);

$SubscriptionData = array(
    "plan_id" => '',
    "start_time" => gmdate("Y-m-d\TH:i:s\Z", strtotime("+10 minutes")),
    "application_context" => array(
        "brand_name" => "Angell EYE Web Hosting",
        "locale" => "en-US",
        "shipping_preference" => "NO_SHIPPING",
        "user_action" => "SUBSCRIBE_NOW",
        'return_url' => $domain . 'demo/rest/paypal-checkout-subscriptions/getSubscriptionProfile.php', 		// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
        'cancel_url' => $domain . 'demo/rest/paypal-checkout-subscriptions/', 
    )
);

/**
 * Now we gather all of the arrays above into a single array.
 */
$PayPalRequestData = array(
    'ProductData' => $ProductData, 
    'PlanData' => $PlanData, 
    'SubscriptionData' => $SubscriptionData, 
);
$PayPalResult = $PayPal->createSubscriptionProfile($PayPalRequestData);

if ($PayPalResult['success']) {
    header('Location: ' . $PayPalResult['approval_url']);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../../error.php');
}