<?php
require_once('../../includes/config.php');
require_once('../../autoload.php');

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature, 
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$CRPPFields = array(
	'token' => 'EC-2A900424LS312982Y', 		// Token returned from PayPal SetExpressCheckout.  Can also use token returned from SetCustomerBillingAgreement.
);
				
$DaysTimestamp = strtotime('now');
$Mo = date('m', $DaysTimestamp);
$Day = date('d', $DaysTimestamp);
$Year = date('Y', $DaysTimestamp);
$StartDateGMT = $Year . '-' . $Mo . '-' . $Day . 'T00:00:00\Z';
				
$ProfileDetails = array(
	'subscribername' => 'Tester Testerson', 					// Full name of the person receiving the product or service paid for by the recurring payment.  32 char max.
	'profilestartdate' => $StartDateGMT, 					// Required.  The date when the billing for this profile begins.  Must be a valid date in UTC/GMT format.
	'profilereference' => '' 					// The merchant's own unique invoice number or reference ID.  127 char max.
);
				
$ScheduleDetails = array(
	'desc' => 'Angell EYE Web Hosting', 		// Required.  Description of the recurring payment.  This field must match the corresponding billing agreement description included in SetExpressCheckout.
	'maxfailedpayments' => '', 			// The number of scheduled payment periods that can fail before the profile is automatically suspended.  
	'autobillamt' => '1' 				// This field indicates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.  Values can be: NoAutoBill or AddToNextBilling
);
				
$BillingPeriod = array(
	'trialbillingperiod' => '', 
	'trialbillingfrequency' => '', 
	'trialtotalbillingcycles' => '', 
	'trialamt' => '', 
	'billingperiod' => 'Day', 		// Required.  Unit for billing during this subscription period.  One of the following: Day, Week, SemiMonth, Month, Year
	'billingfrequency' => '1', 		// Required.  Number of billing periods that make up one billing cycle.  The combination of billing freq. and billing period must be less than or equal to one year. 
	'totalbillingcycles' => '0', 		// the number of billing cycles for the payment period (regular or trial).  For trial period it must be greater than 0.  For regular payments 0 means indefinite...until canceled.  
	'amt' => '10.00', 			// Required.  Billing amount for each billing cycle during the payment period.  This does not include shipping and tax. 
	'currencycode' => 'USD', 		// Required.  Three-letter currency code.
	'shippingamt' => '', 			// Shipping amount for each billing cycle during the payment period.
	'taxamt' => '' 				// Tax amount for each billing cycle during the payment period.
);
				
$ActivationDetails = array(
	'initamt' => '', 			// Initial non-recurring payment amount due immediatly upon profile creation.  Use an initial amount for enrolment or set-up fees.
	'failedinitamtaction' => '', 		// By default, PayPal will suspend the pending profile in the event that the initial payment fails.  You can override this.  Values are: ContinueOnFailure or CancelOnFailure
);
				
$CCDetails = array(
	'creditcardtype' => 'Visa', 		// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
	'acct' => '4635800000971893', 		// Required.  Credit card number.  No spaces or punctuation.  
	'expdate' => '022019', 			// Required.  Credit card expiration date.  Format is MMYYYY
	'cvv2' => '123', 			// Requirements determined by your PayPal account settings.  Security digits for credit card.
	'startdate' => '', 			// Month and year that Maestro or Solo card was issued.  MMYYYY
	'issuenumber' => ''			// Issue number of Maestro or Solo card.  Two numeric digits max.
);
				
$PayerInfo = array(
	'email' => 'tester@hey.com', 		// Email address of payer.
	'payerid' => '', 			// Unique PayPal customer ID for payer.
	'payerstatus' => '', 			// Status of payer.  Values are verified or unverified
	'countrycode' => '', 			// Payer's country of residence in the form of the two letter code.
	'business' => 'Testers, LLC' 		// Payer's business name.
);
				
$PayerName = array(
	'salutation' => '', 			// Payer's salutation.  20 char max.
	'firstname' => 'Tester', 		// Payer's first name.  25 char max.
	'middlename' => '', 			// Payer's middle name.  25 char max.
	'lastname' => 'Testerson', 		// Payer's last name.  25 char max.
	'suffix' => ''				// Payer's suffix.  12 char max.
);
				
$BillingAddress = array(
	'street' => '123 Test Ave.', 		// Required.  First street address.
	'street2' => '', 			// Second street address.
	'city' => 'Grandview', 			// Required.  Name of City.
	'state' => 'MO', 			// Required. Name of State or Province.
	'countrycode' => 'US', 			// Required.  Country code.
	'zip' => '64030', 			// Required.  Postal code of payer.
	'phonenum' => '' 			// Phone Number of payer.  20 char max.
);
					
$ShippingAddress = array(
	'shiptoname' => '', 		// Required if shipping is included.  Person's name associated with this address.  32 char max.
	'shiptostreet' => '', 		// Required if shipping is included.  First street address.  100 char max.
	'shiptostreet2' => '', 		// Second street address.  100 char max.
	'shiptocity' => '', 		// Required if shipping is included.  Name of city.  40 char max.
	'shiptostate' => '', 		// Required if shipping is included.  Name of state or province.  40 char max.
	'shiptozip' => '', 		// Required if shipping is included.  Postal code of shipping address.  20 char max.
	'shiptocountrycode' => '', 	// Required if shipping is included.  Country code of shipping address.  2 char max.
	'shiptophonenum' => ''		// Phone number for shipping address.  20 char max.
);
						
$PayPalRequestData = array(
	'ProfileDetails' => $ProfileDetails, 
	'ScheduleDetails' => $ScheduleDetails, 
	'BillingPeriod' => $BillingPeriod, 
	'CCDetails' => $CCDetails, 
	'PayerInfo' => $PayerInfo, 
	'PayerName' => $PayerName, 
	'BillingAddress' => $BillingAddress
);

$PayPalResult = $PayPal->CreateRecurringPaymentsProfile($PayPalRequestData);

echo '<pre />';
print_r($PayPalResult);
