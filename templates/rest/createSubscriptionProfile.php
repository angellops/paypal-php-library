<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
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

// Validate API mode
$PayPal->ValidateMode('rest');

$ProductData = array(
        "name" => "",
        "description" => "",
        "type" => "",
        "category" => "",
);

$PlanData = array(
        "product_id" => '',
        "name" => "",
        "description" => "",
        "billing_cycles" => array(
                array(
                        "frequency" => array(
                                "interval_unit" => "DAY",
                                "interval_count" => 1
                        ),
                        "tenure_type" => "",
                        "sequence" => 1,
                        "total_cycles" => 0, // 0 = infinite
                        "pricing_scheme" => array(
                                "fixed_price" => array(
                                        "value" => "",
                                        "currency_code" => ""
                                )
                        )
                )
        ),
        "payment_preferences" => array(
                "auto_bill_outstanding" => true,
                "setup_fee_failure_action" => "",
                "payment_failure_threshold" => 3
        )
);

$SubscriptionData = array(
        "plan_id" => '',
        "start_time" => '',
        "subscriber" => array(
                "name" => array(
                        "given_name" => "",
                        "surname" => ""
                ),
                "email_address" => "",
                "shipping_address" => array(
                        "name" => array("full_name" => ""),
                        "address" => array(
                                "address_line_1" => "",
                                "admin_area_2" => "",
                                "admin_area_1" => "",
                                "postal_code" => "",
                                "country_code" => ""
                        )
                )
        ),
        "application_context" => array(
                "brand_name" => "",
                "locale" => "",
                "shipping_preference" => "SET_PROVIDED_ADDRESS",
                "user_action" => "SUBSCRIBE_NOW",
                'return_url' => '',
		'cancel_url' => '', 
        )
);

$PayPalRequestData = array(
	'ProductData' => $ProductData, 
	'PlanData' => $PlanData, 
	'SubscriptionData' => $SubscriptionData, 
);

$PayPalResult = $PayPal->createSubscriptionProfile($PayPalRequestData);

if ($PayPalResult['success']) {
	echo '<a href="' . $PayPalResult['approval_url']. '">Click here to continue.</a><br /><br />';
	echo '<pre />';
	print_r($PayPalResult);
} else {
    	echo 'Error creating order: ' . $PayPalResult['error'];
}