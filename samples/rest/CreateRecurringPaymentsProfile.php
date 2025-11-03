<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$ProductData = array(
        "name" => "Angell EYE Web Hosting",
        "description" => "Web hosting recurring subscription",
        "type" => "SERVICE",
        "category" => "SOFTWARE",
);

$PlanData = array(
        "product_id" => '',
        "name" => "Daily Hosting Plan",
        "description" => "Daily recurring billing for hosting",
        "billing_cycles" => array(
                array(
                        "frequency" => array(
                                "interval_unit" => "DAY",
                                "interval_count" => 1
                        ),
                        "tenure_type" => "REGULAR",
                        "sequence" => 1,
                        "total_cycles" => 0, // 0 = infinite
                        "pricing_scheme" => array(
                                "fixed_price" => array(
                                "value" => "10.00",
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
                        "given_name" => "Tester",
                        "surname" => "Testerson"
                ),
                "email_address" => "tester@hey.com",
                "shipping_address" => array(
                        "name" => array("full_name" => "Tester Testerson"),
                        "address" => array(
                                "address_line_1" => "123 Test Ave.",
                                "admin_area_2" => "Grandview",
                                "admin_area_1" => "MO",
                                "postal_code" => "64030",
                                "country_code" => "US"
                        )
                )
        ),
        "application_context" => array(
                "brand_name" => "Angell EYE Web Hosting",
                "locale" => "en-US",
                "shipping_preference" => "SET_PROVIDED_ADDRESS",
                "user_action" => "SUBSCRIBE_NOW",
                'return_url' => $domain . 'samples/rest/GetRecurringPaymentsProfileDetails.php',
		'cancel_url' => $domain . 'samples/rest/', 
        )
);

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
	echo '<a href="' . $approve_url . '">Click here to continue.</a><br /><br />';
	
	echo '<pre />';
	print_r($PayPalResult);
} else {
    	echo 'Error creating order: ' . $PayPalResult['error'];
}