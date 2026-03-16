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

$ProductData = array(
        "name" => $_SESSION['subscription']['name'],
        "description" => $_SESSION['subscription']['name'],
        "type" => "SERVICE",
        "category" => "SOFTWARE",
);

$PlanData = array(
        "product_id" => '',
        "name" => "Monthly Subscription Plan",
        "description" => "Monthly recurring subscription",
        "billing_cycles" => array(
                array(
                        "frequency" => array(
                                "interval_unit" => strtoupper($_SESSION['subscription']['billing_period']),
                                "interval_count" => $_SESSION['subscription']['billing_frequency']
                        ),
                        "tenure_type" => "REGULAR",
                        "sequence" => 1,
                        "total_cycles" => $_SESSION['subscription']['total_billing_cycles'], // 0 = infinite
                        "pricing_scheme" => array(
                                "fixed_price" => array(
                                        "value" => $_SESSION['subscription']['amount'],  // monthly charge
                                        "currency_code" => "USD"
                                )
                        )
                )
        ),
        "payment_preferences" => array(
                "auto_bill_outstanding" => true,
		"setup_fee_failure_action" => "CONTINUE",
                "payment_failure_threshold" => 3
        )
);

$SubscriptionData = array(
        "plan_id" => '',
        "start_time" => gmdate("Y-m-d\TH:i:s\Z", strtotime("+10 minutes")),
	"subscriber" => array(
                "name" => array(
                        "given_name" => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '',
                        "surname" => isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '',
                ),
                "email_address" => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        ),
        "application_context" => array(
                "brand_name" => "Angell EYE Web Hosting",
                "locale" => "en-US",
                "shipping_preference" => "NO_SHIPPING",
                "user_action" => "SUBSCRIBE_NOW",
                'return_url' => $domain . 'demo/paypal-checkout-subscriptions-shipped/GetRecurringPaymentsProfileDetails.php', 		// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
		'cancel_url' => $domain . 'demo/paypal-checkout-subscriptions-shipped/', 
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

$PayPalResult = $PayPal->CreateSubscriptionProfile($PayPalRequestData);

if ($PayPalResult['success']) {
    $approve_url = '';
    if (!empty($PayPalResult['response']['links'])) {
        foreach ($PayPalResult['response']['links'] as $link) {
            if (isset($link['rel']) && $link['rel'] === 'approve') {
                $approve_url = $link['href'];
                break;
            }
        }
    }

    header('Location: ' . $approve_url);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}