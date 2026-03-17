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
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$CRPPFields = array(
    'token' => '', 			// Token returned from PayPal SetExpressCheckout.  Can also use token returned from SetCustomerBillingAgreement.
    'softdescriptor' => '',             // Information that is usually displayed in the account holder's statement.  23 char max.
    'softdescriptorcity' => '',         // A unique phone number, emaili address or URL, which is displayed on the account holder's statement.
);
				
$ProfileDetails = array(
	'subscribername' => '', 	// Full name of the person receiving the product or service paid for by the recurring payment.  32 char max.
	'profilestartdate' => '', 	// Required.  The date when the billing for this profile begins.  Must be a valid date in UTC/GMT format.
	'profilereference' => '' 	// The merchant's own unique invoice number or reference ID.  127 char max.
);
				
$ScheduleDetails = array(
	'desc' => '', 			// Required.  Description of the recurring payment.  This field must match the corresponding billing agreement description included in SetExpressCheckout.
	'maxfailedpayments' => '', 	// The number of scheduled payment periods that can fail before the profile is automatically suspended.  
	'autobilloutamt' => '' 		// This field indicates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.  Values can be: NoAutoBill or AddToNextBilling
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
	'shippingamt' => '', 			// Shipping amount for each billing cycle during the payment period.
	'taxamt' => '' 				// Tax amount for each billing cycle during the payment period.
);
				
$ActivationDetails = array(
	'initamt' => '', 			// Initial non-recurring payment amount due immediatly upon profile creation.  Use an initial amount for enrolment or set-up fees.
	'failedinitamtaction' => '', 		// By default, PayPal will suspend the pending profile in the event that the initial payment fails.  You can override this.  Values are: ContinueOnFailure or CancelOnFailure
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
	'email' => '', 		// Email address of payer.
	'payerid' => '', 	// Unique PayPal customer ID for payer.
	'payerstatus' => '', 	// Status of payer.  Values are verified or unverified
	'business' => '' 	// Payer's business name.
);
				
$PayerName = array(
	'salutation' => '', 	// Payer's salutation.  20 char max.
	'firstname' => '', 	// Payer's first name.  25 char max.
	'middlename' => '', 	// Payer's middle name.  25 char max.
	'lastname' => '', 	// Payer's last name.  25 char max.
	'suffix' => ''		// Payer's suffix.  12 char max.
);
				
$BillingAddress = array(
	'street' => '', 	// Required.  First street address.
	'street2' => '', 	// Second street address.
	'city' => '', 		// Required.  Name of City.
	'state' => '', 		// Required. Name of State or Province.
	'countrycode' => '', 	// Required.  Country code.
	'zip' => '', 		// Required.  Postal code of payer.
	'phonenum' => '' 	// Phone Number of payer.  20 char max.
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
						
// For order items you populate a nested array with multiple $Item arrays.  Normally you'll be looping through cart items to populate the $Item 
// array and then push it into the $OrderItems array at the end of each loop for an entire collection of all items in $OrderItems.

$OrderItems = array();		
$Item	 = array(
	'l_itemcategory' => '', 	// One of the following values:  Digital, Physical
	'l_name' => '', 		// Item Name.  127 char max.
	'l_desc' => '', 		// Item description.  127 char max.
	'l_amt' => '', 			// Cost of individual item.
	'l_number' => '', 		// Item Number.  127 char max.
	'l_qty' => '', 			// Item quantity.  Must be any positive integer.  
	'l_taxamt' => '' 		// Item's sales tax amount.
);
array_push($OrderItems, $Item);

$PayPalRequestData = array(
	'CRPPFields' => $CRPPFields, 
	'ProfileDetails' => $ProfileDetails, 
	'ScheduleDetails' => $ScheduleDetails, 
	'BillingPeriod' => $BillingPeriod, 
	'ActivationDetails' => $ActivationDetails, 
	'CCDetails' => $CCDetails, 
	'PayerInfo' => $PayerInfo, 
	'PayerName' => $PayerName, 
	'BillingAddress' => $BillingAddress, 
	'ShippingAddress' => $ShippingAddress, 
	'OrderItems' => $OrderItems
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->CreateRecurringPaymentsProfile($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
