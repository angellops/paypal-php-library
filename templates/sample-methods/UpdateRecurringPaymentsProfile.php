<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature, 
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
	'PayPalAPIUpgrade' => $api_upgrade,
    'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);


if ( $api_mode === 'rest' ) { 

	$ReplaceFields = array(
		"op" => "",
			"path" => "",
			"value" => [
					"currency_code" => "",
					"value" => ""
			]
	);

	$Patches = array($ReplaceFields);

	// Prepare request arrays
	$PayPalRequestData = array(
			'subscription_id' => '',
			'patches' => $Patches,
	);


	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->UpdateSubscriptionProfile($PayPalRequestData);

} else {

	// Prepare request arrays
	$URPPFields = array(
		'profileid' => '',		 		// Required.  Recurring payments ID.
		'note' => '', 					// Note about the reason for the update to the profile.  Included in EC profile notification emails and in details pages.
		'desc' => '', 					// Description of the recurring payment profile.
		'subscribername' => '', 			// Full name of the person receiving the product or service paid for by the recurring payment profile.
		'profilereference' => '', 			// The merchant's own unique reference or invoice number.
		'additionalbillingcycles' => '', 		// The number of additional billing cycles to add to this profile.
		'amt' => '',	 				// Billing amount for each cycle in the subscription, not including shipping and tax.  Express Checkout profiles can only be updated by 20% every 180 days.
		'shippingamt' => '', 				// Shipping amount for each billing cycle during the payment period.
		'taxamt' => '', 				// Tax amount for each billing cycle during the payment period.
		'outstandingamt' => '', 			// The current past-due or outstanding amount.  You can only decrease this amount.  
		'autobilloutamt' => '', 			// This field indicates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.
		'maxfailedpayments' => '', 			// The number of failed payments allowed before the profile is automatically suspended.  The specified value cannot be less than the current number of failed payments for the profile.
		'profilestartdate' => ''			// The date when the billing for this profile begins.  UTC/GMT format.
	);

	$BillingAddress = array(
		'street' => '', 		// Required.  First street address.
		'street2' => '', 		// Second street address.
		'city' => '', 			// Required.  Name of City.
		'state' => '', 			// Required. Name of State or Province.
		'countrycode' => '', 		// Required.  Country code.
		'zip' => '', 			// Required.  Postal code of payer.
		'phonenum' => '' 		// Phone Number of payer.  20 char max.
	);

	$ShippingAddress = array(
		'shiptoname' => '', 		// Required if shipping is included.  Person's name associated with this address.  32 char max.
		'shiptostreet' => '', 		// Required if shipping is included.  First street address.  100 char max.
		'shiptostreet2' => '', 		// Second street address.  100 char max.
		'shiptocity' => '', 		// Required if shipping is included.  Name of city.  40 char max.
		'shiptostate' => '', 		// Required if shipping is included.  Name of state or province.  40 char max.
		'shiptozip' => '', 		// Required if shipping is included.  Postal code of shipping address.  20 char max.
		'shiptocountry' => '', 		// Required if shipping is included.  Country code of shipping address.  2 char max.
		'shiptophonenum' => ''		// Phone number for shipping address.  20 char max.
	);

	$BillingPeriod = array(
		'trialbillingperiod' => '', 
		'trialbillingfrequency' => '', 
		'trialtotalbillingcycles' => '', 
		'trialamt' => '', 
		'billingperiod' => '', 			// Required.  Unit for billing during this subscription period.  One of the following: Day, Week, SemiMonth, Month, Year
		'billingfrequency' => '', 		// Required.  Number of billing periods that make up one billing cycle.  The combination of billing freq. and billing period must be less than or equal to one year. 
		'totalbillingcycles' => '', 		// the number of billing cycles for the payment period (regular or trial).  For trial period it must be greater than 0.  For regular payments 0 means indefinite...until canceled.  
		'amt' => '', 				// Required.  Billing amount for each billing cycle during the payment period.  This does not include shipping and tax. 
		'currencycode' => '', 			// Required.  Three-letter currency code.
	);

	$CCDetails = array(
		'creditcardtype' => '', 	// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
		'acct' => '', 			// Required.  Credit card number.  No spaces or punctuation.  
		'expdate' => '', 		// Required.  Credit card expiration date.  Format is MMYYYY
		'cvv2' => '', 			// Requirements determined by your PayPal account settings.  Security digits for credit card.
		'startdate' => '', 		// Month and year that Maestro or Solo card was issued.  MMYYYY
		'issuenumber' => ''		// Issue number of Maestro or Solo card.  Two numeric digits max.
	);

	$PayerInfo = array(
		'email' => '', 			// Payer's email address.
		'firstname' => '', 		// Required.  Payer's first name.
		'lastname' => ''		// Required.  Payer's last name.
	);
				
	$PayPalRequestData = array(
		'URPPFields' => $URPPFields, 
		'BillingAddress' => $BillingAddress, 
		'ShippingAddress' => $ShippingAddress, 
		'BillingPeriod' => $BillingPeriod, 
		'CCDetails' => $CCDetails, 
		'PayerInfo' => $PayerInfo
	);

	// Pass data into class for processing with PayPal and load the response array into $PayPalResult
	$PayPalResult = $PayPal->UpdateRecurringPaymentsProfile($PayPalRequestData);

}

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
