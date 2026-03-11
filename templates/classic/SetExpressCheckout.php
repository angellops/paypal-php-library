<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

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

$PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

// Prepare request arrays
$SECFields = array(
					'token' => '', 								// A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
					'maxamt' => '', 							// The expected maximum total amount the order will be, including S&H and sales tax.
					'returnurl' => '', 							// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
					'cancelurl' => '', 							// Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
					'callback' => '', 							// URL to which the callback request from PayPal is sent.  Must start with https:// for production.
					'callbacktimeout' => '', 					// An override for you to request more or less time to be able to process the callback request and response.  Acceptable range for override is 1-6 seconds.  If you specify greater than 6 PayPal will use default value of 3 seconds.
					'callbackversion' => '', 					// The version of the Instant Update API you're using.  The default is the current version.							
					'reqconfirmshipping' => '', 				// The value 1 indicates that you require that the customer's shipping address is Confirmed with PayPal.  This overrides anything in the account profile.  Possible values are 1 or 0.
					'noshipping' => '', 						// The value 1 indicates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.					
					'addroverride' => '', 						// The value 1 indicates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
					'localecode' => '', 						// Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
					'pagestyle' => '', 							// Sets the Custom Payment Page Style for payment pages associated with this button/link.  
					'hdrimg' => '', 							// URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
					'hdrbordercolor' => '', 					// Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.  
					'hdrbackcolor' => '', 						// Sets the background color for the header of the payment page.  Default is white.  
					'payflowcolor' => '', 						// Sets the background color for the payment page.  Default is white.
					'cartbordercolor' => '', 					// The HTML hex code for your principal identifying color.  PayPal blends your color to white in a gradient fill that borders the cart review area of the PayPal checkout user interface.  6 single-byte hexadecimal chars. that represent an HTML hex code for a color.
					'logoimg' => '', 							// A URL to your logo image.  Formats:  .gif, .jpg, .png.  190x60.  PayPal places your logo image at the top of the cart review area.  This logo needs to be stored on a https:// server.
					'skipdetails' => '', 						// This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
					'email' => '', 								// Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
					'solutiontype' => '', 						// Type of checkout flow.  Must be Sole (express checkout for auctions) or Mark (normal express checkout)
					'landingpage' => '', 						// Type of PayPal page to display.  Can be Billing or Login.  If billing it shows a full credit card form.  If Login it just shows the login screen.
					'channeltype' => '', 						// Type of channel.  Must be Merchant (non-auction seller) or eBayItem (eBay auction)
					'giropaysuccessurl' => '', 					// The URL on the merchant site to redirect to after a successful giropay payment.  Only use this field if you are using giropay or bank transfer payment methods in Germany.
					'giropaycancelurl' => '', 					// The URL on the merchant site to redirect to after a canceled giropay payment.  Only use this field if you are using giropay or bank transfer methods in Germany.
					'banktxnpendingurl' => '',  				// The URL on the merchant site to transfer to after a bank transfter payment.  Use this field only if you are using giropay or bank transfer methods in Germany.
					'brandname' => '', 							// A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
					'customerservicenumber' => '', 				// Merchant Customer Service number displayed on the PayPal Review page. 16 char max.
					'giftmessageenable' => '', 					// Enable gift message widget on the PayPal Review page. Allowable values are 0 and 1
					'giftreceiptenable' => '', 					// Enable gift receipt widget on the PayPal Review page. Allowable values are 0 and 1
					'giftwrapenable' => '', 					// Enable gift wrap widget on the PayPal Review page.  Allowable values are 0 and 1.
					'giftwrapname' => '', 						// Label for the gift wrap option such as "Box with ribbon".  25 char max.
					'giftwrapamount' => '', 					// Amount charged for gift-wrap service.
					'buyeremailoptionenable' => '', 			// Enable buyer email opt-in on the PayPal Review page. Allowable values are 0 and 1
					'surveyquestion' => '', 					// Text for the survey question on the PayPal Review page. If the survey question is present, at least 2 survey answer options need to be present.  50 char max.
					'surveyenable' => '', 						// Enable survey functionality. Allowable values are 0 and 1
					'totaltype' => '', 							// Enables display of "estimated total" instead of "total" in the cart review area.  Values are:  Total, EstimatedTotal
					'notetobuyer' => '', 						// Displays a note to buyers in the cart review area below the total amount.  Use the note to tell buyers about items in the cart, such as your return policy or that the total excludes shipping and handling.  127 char max.
					'buyerid' => '', 							// The unique identifier provided by eBay for this buyer. The value may or may not be the same as the username. In the case of eBay, it is different. 255 char max.
					'buyerusername' => '', 						// The user name of the user at the marketplaces site.
					'buyerregistrationdate' => '',  			// Date when the user registered with the marketplace.
					'allowpushfunding' => '', 					// Whether the merchant can accept push funding.  0 = Merchant can accept push funding : 1 = Merchant cannot accept push funding.			
					'userselectedfundingsource' => '', 			// This element could be used to specify the preferred funding option for a guest user.  However, the LANDINGPAGE element must also be set to Billing.  Otherwise, it is ignored.  Values:  BML, ChinaUnionPay, CreditCard, ELV, Finance, QIWI
					'taxidtype' => '', 							// The buyer's tax ID type.  This field is required for Brazil and used for Brazil only.  Values:  BR_CPF for individuals and BR_CNPJ for businesses.
					'taxid' => ''								// The buyer's tax ID.  This field is required for Brazil and used for Brazil only.  The tax ID is 11 single-byte characters for individutals and 14 single-byte characters for businesses.
				);

// Basic array of survey choices.  Nothing but the values should go in here.  
$SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');

$Payments = array();
$Payment = array(
    'amt' => '', 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
    'currencycode' => '', 					// A three-character currency code.  Default is USD.
    'itemamt' => '', 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.
    'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
    'shipdiscamt' => '', 					// Shipping discount for this order, specified as a negative number.
    'insuranceamt' => '', 					// Total shipping insurance costs for this order.
    'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
    'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
    'taxamt' => '', 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
    'desc' => '', 							// Description of items on the order.  127 char max.
    'custom' => '', 						// Free-form field for your own use.  256 char max.
    'invnum' => '', 						// Your own invoice or tracking number.  127 char max.
    'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
    'multishipping' => '', 					// The value 1 indicates that this payment is associated with multiple shipping addresses.
    'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
    'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
    'shiptostreet2' => '', 					// Second street address.  100 char max.
    'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
    'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
    'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
    'shiptocountrycode' => '', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
    'shiptophonenum' => '',  				// Phone number for shipping address.  20 char max.
    'notetext' => '', 						// Note to the merchant.  255 char max.
    'allowedpaymentmethod' => '', 			// The payment method type.  Specify the value InstantPaymentOnly.
    'paymentaction' => '', 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
    'paymentrequestid' => '',  				// A unique identifier of the specific payment request, which is required for parallel payments.
    'sellerpaypalaccountid' => '',			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
    'transactionid' => '',					// Transaction ID number of the transaction that was created.  You can specify up to 10 payments.
    'bucketcategorytype' => '',             // The category of a payment.  Values are:  1 - International shipping, 2 - Local delivery, 3 - BOPIS (Buy online pick-up in store), 4 - PUDO (Pick up drop off)
    'location_type' => '',                  // The type of merchant location.  Set this field if the items purchased will not be shipped, such as, BOPIS transactions.  Values are: 1 - Consumer, 2 - Store, for BOPIS transactions, 3 - PUDO
    'location_id' => '',                    // The Location ID specified by the merchant for BOPIS or PUDO transactions.
);
				
$PaymentOrderItems = array();
$Item = array(
			'name' => '', 								// Item name. 127 char max.
			'desc' => '', 								// Item description. 127 char max.
			'amt' => '', 								// Cost of item.
			'number' => '', 							// Item number.  127 char max.
			'qty' => '', 								// Item qty on order.  Any positive integer.
			'taxamt' => '', 							// Item sales tax
			'itemurl' => '', 							// URL for the item.
			'itemcategory' => '', 						// One of the following values:  Digital, Physical
			'itemweightvalue' => '', 					// The weight value of the item.
			'itemweightunit' => '', 					// The weight unit of the item.
			'itemheightvalue' => '', 					// The height value of the item.
			'itemheightunit' => '', 					// The height unit of the item.
			'itemwidthvalue' => '', 					// The width value of the item.
			'itemwidthunit' => '', 						// The width unit of the item.
			'itemlengthvalue' => '', 					// The length value of the item.
			'itemlengthunit' => '',  					// The length unit of the item.
			'ebayitemnumber' => '', 					// Auction item number.  
			'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.  
			'ebayitemorderid' => '',  					// Auction order ID number.
			'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
			);
array_push($PaymentOrderItems, $Item);
$Payment['order_items'] = $PaymentOrderItems;

array_push($Payments, $Payment);

$BuyerDetails = array(
						'buyerid' => '', 				// The unique identifier provided by eBay for this buyer.  The value may or may not be the same as the username.  In the case of eBay, it is different.  Char max 255.
						'buyerusername' => '', 			// The username of the marketplace site.
						'buyerregistrationdate' => ''	// The registration of the buyer with the marketplace.
						);
						
// For shipping options we create an array of all shipping choices similar to how order items works.
$ShippingOptions = array();
$Option = array(
				'l_shippingoptionisdefault' => '', 				// Shipping option.  Required if specifying the Callback URL.  true or false.  Must be only 1 default!
				'l_shippingoptionname' => '', 					// Shipping option name.  Required if specifying the Callback URL.  50 character max.
				'l_shippingoptionlabel' => '', 					// Shipping option label.  Required if specifying the Callback URL.  50 character max.
				'l_shippingoptionamount' => '' 					// Shipping option amount.  Required if specifying the Callback URL.  
				);
array_push($ShippingOptions, $Option);
		
$BillingAgreements = array();
$Item = array(
			  'l_billingtype' => '', 							// Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
			  'l_billingagreementdescription' => '', 			// Required for recurring payments.  Description of goods or services associated with the billing agreement.  
			  'l_paymenttype' => '', 							// Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
			  'l_billingagreementcustom' => ''					// Custom annotation field for your own use.  256 char max.
			  );

array_push($BillingAgreements, $Item);

$PayPalRequestData = array(
					   'SECFields' => $SECFields, 
					   'SurveyChoices' => $SurveyChoices, 
					   'Payments' => $Payments, 
					   'BuyerDetails' => $BuyerDetails, 
					   'ShippingOptions' => $ShippingOptions, 
					   'BillingAgreements' => $BillingAgreements
					   );

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<a href="' . $PayPalResult['REDIRECTURL'] . '">Click here to continue.</a><br /><br />';
echo '<pre />';
print_r($PayPalResult);
