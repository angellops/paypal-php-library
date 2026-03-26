<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
    'PayPalAPIUpgrade' => $api_upgrade,
	'ClientID' => $rest_client_id_2,
	'ClientSecret' => $rest_client_secret_2,
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Initialize an empty array to store payment capabilities
$capabilities = [];

// Retrieve the buyer's country code from the session (e.g., 'US', 'FR')
$country = $_SESSION['buyer_country'];

// Define the list of countries where Apple Pay and Google Pay are supported
$acdcCountries = ['US', 'GB', 'DE', 'FR', 'AU'];

/**
 * Check if the buyer's country is in the supported list.
 * If true, enable digital wallets; otherwise, keep the capabilities list empty.
 */
if (in_array($country, $acdcCountries)) {
    $capabilities = ['APPLE_PAY', 'GOOGLE_PAY'];
} else {
    $capabilities = [];
}

/**
 * Here we are setting up the parameters required to initiate
 * PayPal Partner Referral (Merchant Onboarding).
 *
 * This request is used by the platform/partner to onboard
 * a new merchant account and grant required API permissions.
 *
 * Once the merchant completes onboarding on PayPal,
 * they will be redirected back to the return_url provided below.
 */
$PayPalRequestData = [
    'tracking_id' => uniqid('PP_REFERRAL_'),                // Unique ID to track this onboarding flow on the partner side
    'operations' => [[
        'operation' => 'API_INTEGRATION',                   // Defines that we are requesting API access for the onboarded merchant.
        'api_integration_preference' => [
            'rest_api_integration' => [
                'integration_method' => 'PAYPAL',           // Specifies PayPal REST API integration.
                'integration_type' => 'THIRD_PARTY',        // Indicates this is a third-party (partner) integration.
                'third_party_details' => [
                    'features' => [
                        'PAYMENT',                          // Allows the partner to process payments on behalf of the merchant.
                        'REFUND',                           // Allows issuing refunds via API.
                    ]     
                ]
            ]
        ]
    ]],
    'products' => [ 
        'PAYMENT_METHODS',                                  // Requests Payment Methods capability for the onboarded merchant.
    ], 
    'legal_consents' => [[
        'type' => 'SHARE_DATA_CONSENT',                     // Merchant consents to share account data with the partner.
        'granted' => true                                   // Consent must be explicitly granted for onboarding to succeed.
    ]],
    'partner_config_override' => [
        'return_url' => $domain . 'demo/rest/paypal-partner-referral-onboarding/verifyMerchantOnboarding.php?onboarding=true',        // Required. URL where the merchant is redirected after completing onboarding on PayPal.
        'return_url_description' => 'Return after onboarding'                                                       // Description shown to the merchant during the onboarding flow.
    ]
];

/**
 * If the capabilities array is not empty, add it to the PayPal request data.
 * This prevents sending an empty 'capabilities' key in the API call.
 */
if (!empty($capabilities)) {
    $PayPalRequestData['capabilities'] = $capabilities;
}

/**
 * Initiates the PayPal Partner Merchant Onboarding flow.
 *
 * This calls the createMerchantOnboarding method with the prepared
 * $PayPalRequestData, which generates a referral URL for the seller.
 * The seller is redirected to PayPal to complete onboarding and grant
 * the required permissions to the partner application.
 */
$PayPalResult = $PayPal->createMerchantOnboarding($PayPalRequestData);

if( $PayPalResult['success'] ) {
    header('Location: ' . $PayPalResult['approval_url']);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['error'];
    header('Location: ../error.php');
}