<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

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

/**
 * Token returned from PayPal SetExpressCheckout.
 */
$CRPPFields = array(
	'token' => isset($_SESSION['paypal_token']) ? $_SESSION['paypal_token'] : '',
);

/**
 * Here we are setting up the parameters for a basic Express Checkout flow.
 *
 * The template provided at ../../vendor/angelleye/paypal-php-library/samples/classic/CreateRecurringPaymentsProfile.php
 * contains a lot more parameters that we aren't using here, so I've removed them to keep this clean.
 *
 * $domain used here is set in the config file.
 */
$DaysTimestamp = strtotime('now');
$Mo = date('m', $DaysTimestamp);
$Day = date('d', $DaysTimestamp);
$Year = date('Y', $DaysTimestamp);
$StartDateGMT = $Year . '-' . $Mo . '-' . $Day . 'T00:00:00\Z';

$firstname = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
$lastname = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';

/**
 * Subscriber Details
 */
$ProfileDetails = array(
	'subscribername' => $firstname.' '.$lastname, 					// Full name of the person receiving the product or service paid for by the recurring payment.  32 char max.
	'profilestartdate' => $StartDateGMT, 						// Required.  The date when the billing for this profile begins.  Must be a valid date in UTC/GMT format.
	'profilereference' => '' 							// The merchant's own unique invoice number or reference ID.  127 char max.
);

/**
 * Subscriber's Schedule Details
 */
$ScheduleDetails = array(
	'desc' => $_SESSION['subscription_name'], 		// Required.  Description of the recurring payment.  This field must match the corresponding billing agreement description included in SetExpressCheckout.
	'maxfailedpayments' => '3', 				// The number of scheduled payment periods that can fail before the profile is automatically suspended.  
	'autobillamt' => 'AddToNextBilling' 			// This field indicates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.  Values can be: NoAutoBill or AddToNextBilling
);
				
$BillingPeriod = array(
	'trialbillingperiod' => '', 
	'trialbillingfrequency' => '', 
	'trialtotalbillingcycles' => '', 
	'trialamt' => '', 
	'billingperiod' => $_SESSION['billingperiod'], 				// Required.  Unit for billing during this subscription period.  One of the following: Day, Week, SemiMonth, Month, Year
	'billingfrequency' => $_SESSION['billingfrequency'], 			// Required.  Number of billing periods that make up one billing cycle.  The combination of billing freq. and billing period must be less than or equal to one year. 
	'totalbillingcycles' => $_SESSION['totalbillingcycles'], 		// the number of billing cycles for the payment period (regular or trial).  For trial period it must be greater than 0.  For regular payments 0 means indefinite...until canceled.  
	'amt' => $_SESSION['recurring_items'][1]['amt'], 					// Required.  Billing amount for each billing cycle during the payment period.  This does not include shipping and tax. 
	'currencycode' => 'USD', 						// Required.  Three-letter currency code.
	'shippingamt' => $_SESSION['shopping_cart']['shipping'], 		// Shipping amount for each billing cycle during the payment period.
	'taxamt' => $_SESSION['shopping_cart']['tax'] 				// Tax amount for each billing cycle during the payment period.
);
				
$ActivationDetails = array(
	'initamt' => $_SESSION['recurring_items'][0]['amt'], 		// Initial non-recurring payment amount due immediatly upon profile creation.  Use an initial amount for enrolment or set-up fees.
	'failedinitamtaction' => '', 				// By default, PayPal will suspend the pending profile in the event that the initial payment fails.  You can override this.  Values are: ContinueOnFailure or CancelOnFailure
);

/**
 * Payer details
 */
$PayerInfo = array(
	'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '', 				// Email address of payer.
	'payerid' => isset($_SESSION['paypal_payer_id']) ? $_SESSION['paypal_payer_id'] : '',  		// Unique PayPal customer ID for payer.
	'payerstatus' => '', 										// Status of payer.  Values are verified or unverified
	'countrycode' => '', 										// Payer's country of residence in the form of the two letter code.
	'business' => '' 										// Payer's business name.
);
				
$PayerName = array(
	'salutation' => '', 					// Payer's salutation.  20 char max.
	'firstname' => $firstname,		 		// Payer's first name.  25 char max.
	'middlename' => '', 					// Payer's middle name.  25 char max.
	'lastname' => $lastname,	 			// Payer's last name.  25 char max.
	'suffix' => ''						// Payer's suffix.  12 char max.
);

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
                                "interval_unit" => "MONTH",
                                "interval_count" => 1
                        ),
                        "tenure_type" => "REGULAR",
                        "sequence" => 1,
                        "total_cycles" => 0, // 0 = infinite
                        "pricing_scheme" => array(
                                "fixed_price" => array(
                                        "value" => $_SESSION['recurring_items'][1]['amt'],  // monthly charge
                                        "currency_code" => "USD"
                                )
                        )
                )
        ),
        "payment_preferences" => array(
                "auto_bill_outstanding" => true,
		"setup_fee" => array(
			"value" => $_SESSION['recurring_items'][0]['amt'], // one-time setup fee
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
                'return_url' => $domain . 'demo/paypal-checkout-subscriptions/GetRecurringPaymentsProfileDetails.php', 		// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
		'cancel_url' => $domain . 'demo/paypal-checkout-subscriptions/', 
        )
);

/**
 * Now we gather all of the arrays above into a single array.
 */
$PayPalRequestData = array(
    'ProfileDetails' => $ProfileDetails,
    'ScheduleDetails' => $ScheduleDetails,
    'BillingPeriod' => $BillingPeriod,
    'PayerInfo' => $PayerInfo,
    'PayerName' => $PayerName,
    'CRPPFields' => $CRPPFields,
    'ActivationDetails' => $ActivationDetails,
    'ProductData' => $ProductData, 
    'PlanData' => $PlanData, 
    'SubscriptionData' => $SubscriptionData, 
);

if ( $api_mode === 'classic' ) {
    $PayPalResult = $PayPal->CreateRecurringPaymentsProfile($PayPalRequestData);

    $_SESSION['RecurringProfileId'] = $PayPalResult['PROFILEID'];
    header('Location: order-complete.php');
} elseif( $api_mode == 'rest' ) {
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
    }
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}