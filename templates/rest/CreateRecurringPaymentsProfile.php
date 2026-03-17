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
        "name" => "",
        "description" => "",
        "type" => "",
        "category" => "",
);

$PlanData = array(
        "product_id" => "",
        "name" => "",
        "description" => "",
        "billing_cycles" => array(
                array(
                        "frequency" => array(
                                "interval_unit" => "",
                                "interval_count" => ""
                        ),
                        "tenure_type" => "",
                        "sequence" => "",
                        "total_cycles" => "", // 0 = infinite
                        "pricing_scheme" => array(
                                "fixed_price" => array(
                                        "value" => "",
                                        "currency_code" => ""
                                )
                        )
                )
        ),
        "payment_preferences" => array(
                "auto_bill_outstanding" => "",
                "setup_fee_failure_action" => "",
                "payment_failure_threshold" => ""
        )
);

$SubscriptionData = array(
        "plan_id" => "",
        "start_time" => "",
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
                "shipping_preference" => "",
                "user_action" => "",
                'return_url' => "",
		'cancel_url' => "", 
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