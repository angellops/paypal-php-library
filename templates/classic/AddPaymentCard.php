<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'DeveloperAccountEmail' => $developer_account_email,
	'ApplicationID' => $application_id,
	'DeviceID' => '',
	'IPAddress' => $_SERVER['REMOTE_ADDR'],
	'PayPalAPIMode' => $api_mode,
	'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'isAdaptive' => true,
	'APISubject' => $api_subject,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$AddPaymentCardFields = array(		
	'AccountID' => '', 			// The ID number of the PayPal account for which the payment card is being added.  You must specify either AccountID or EmailAdddress
	'CardNumber' => '', 			// Required.  The credit card number.
	'CardOwnerDateOfBirth' => '', 		// The date of birth of the card holder.
	'CardType' => '', 			// Required.  The type of card being added.  Values are:  Visa, MasterCard, AmericanExpress, Discover, SwitchMaestro, Solo, CarteAurore, CarteBleue, Cofinoga, 4etoiles, CarteAura, TarjetaAurora, JCB
	'CardVerificationNumber' => '', 	// The verification code for the card.  Generally required for calls where ConfirmationType is set to NONE.  With the appropriate account review, this param is optional.
	'ConfirmationType' => '', 		// Required.  Whether the account holder is redirected to PayPal.com to confirm the card addition.  Values are:  WEB, NONE
	'CreateAccountKey' => '', 		// The key returned in a CreateAccount response.  Required for calls where the ConfirmationType is NONE.
	'EmailAddress' => '', 			// Email address of the account holder adding the card.  Must specify either AccountID or EmailAddress.
	'IssueNumber' => '', 			// The 2-digit issue number for Switch, Maestro, and Solo cards.
	'StartDate' => '' 			// The element containing the start date for the payment card.
);
							
$NameOnCard = array(
	'Salutation' => '', 		// A salutation for the card owner.
	'FirstName' => '', 		// Required.  First name of the card holder.
	'MiddleName' => '', 		// Middle name of the card holder.
	'LastName' => '',	 	// Required.  Last name of the card holder.
	'Suffix' => ''			// A suffix for the card holder.
);
							
$BillingAddress = array(
	'Line1' => '', 		// Required.  Billing street address.
	'Line2' => '', 		// Billing street address 2
	'City' => '', 		// Required.  Billing city.
	'State' => '', 		// Billing state.
	'PostalCode' => '',	// Billing postal code
	'CountryCode' => ''	// Required.  Billing country code.
);

$ExpirationDate = array(
	'Month' => '', 		// Expiration month.
	'Year' => ''		// Required.  Expiration Year.
);
							
$WebOptions = array(
	'CancelURL' => '', 			// The URL to which the user is returned when they cancel the flow at PayPal.com
	'CancelURLDescription' => '', 		// A description for the CancelURL
	'ReturnURL' => '', 			// The URL to which the user is returned when they complete the process.
	'ReturnURLDescription' => ''		// A description for the ReturnURL
);

$PayPalRequestData = array(
	'AddPaymentCardFields' => $AddPaymentCardFields, 
	'NameOnCard' => $NameOnCard, 
	'BillingAddress' => $BillingAddress, 
	'ExpirationDate' => $ExpirationDate, 
	'WebOptions' => $WebOptions
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->Adaptive->AddPaymentCard($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);
