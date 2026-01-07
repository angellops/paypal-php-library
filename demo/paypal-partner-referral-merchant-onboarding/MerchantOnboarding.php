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
    'products' => ['EXPRESS_CHECKOUT'],                     // Requests Express Checkout capability for the onboarded merchant.
    'legal_consents' => [[
        'type' => 'SHARE_DATA_CONSENT',                     // Merchant consents to share account data with the partner.
        'granted' => true                                   // Consent must be explicitly granted for onboarding to succeed.
    ]],
    'partner_config_override' => [
        'return_url' => $domain . 'demo/paypal-partner-referral-merchant-onboarding/verifyMerchantOnboarding.php?onbaording=true',        // Required. URL where the merchant is redirected after completing onboarding on PayPal.
        'return_url_description' => 'Return after onboarding'                                                       // Description shown to the merchant during the onboarding flow.
    ]
];

/**
 * Initiates the PayPal Partner Merchant Onboarding flow.
 *
 * This calls the createMerchantOnboarding method with the prepared
 * $PayPalRequestData, which generates a referral URL for the seller.
 * The seller is redirected to PayPal to complete onboarding and grant
 * the required permissions to the partner application.
 */
$PayPalResult = $PayPal->createMerchantOnboarding($PayPalRequestData);

$redirect_url = '';
if( $PayPalResult['success'] ) {
    if( isset( $PayPalResult['full_response']['links'] ) )
    foreach( $PayPalResult['full_response']['links'] as $link ) {
        if ($link['rel'] === 'action_url') {
            $redirect_url = $link['href'];
        }
    }
    header('Location: ' . $redirect_url);
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
    header('Location: ../error.php');
}