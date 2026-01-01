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

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$PayPalRequestData = array(
	'token' => isset($_GET['token']) ? $_GET['token'] : '',
        'ba_token' => isset($_GET['ba_token']) ? $_GET['ba_token'] : '',
        'subscription_id' => isset($_GET['subscription_id']) ? $_GET['subscription_id'] : '',
);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
$PayPalResult = $PayPal->GetSubscriptionProfile($PayPalRequestData);

if( $PayPalResult['success'] || ( $api_upgrade && $PayPalResult['ACK'] && strtoupper($PayPalResult['ACK']) === 'SUCCESS' ) ) {
        $full_response = ( !$api_upgrade && isset( $PayPalResult['full_response'] ) ) ? $PayPalResult['full_response'] : [];
        $subscriber_data = ( !$api_upgrade && !empty( $full_response ) && isset( $full_response['subscriber'] ) ) ? $full_response['subscriber'] : [];
        $name_data = ( !$api_upgrade && !empty( $subscriber_data ) && isset( $subscriber_data['name'] ) ) ? $subscriber_data['name'] : [];
        
        $_SESSION['paypal_payer_id'] = ( ! $api_upgrade ) 
                                        ? ( ( !empty( $subscriber_data ) && isset( $subscriber_data['payer_id'] ) ) ? $subscriber_data['payer_id'] : '' )
                                        : ( isset( $PayPalResult['PAYERID'] ) ? $PayPalResult['PAYERID'] : '' );
        $_SESSION['phone_number'] = ( ! $api_upgrade ) 
                                ? ( ( !empty( $subscriber_data ) && isset( $subscriber_data['phone_number'] ) ) ? $subscriber_data['phone_number'] : '' )
                                : ( isset( $PayPalResult['PHONENUMBER'] ) ? $PayPalResult['PHONENUMBER'] : '' );
        $_SESSION['email'] = ( ! $api_upgrade )
                        ? ( ( !empty( $subscriber_data ) && isset( $subscriber_data['email_address'] ) ) ? $subscriber_data['email_address'] : '' )
                        : ( isset( $PayPalResult['SUBSCRIBEREMAIL'] ) ? $PayPalResult['SUBSCRIBEREMAIL'] : '' );
        $_SESSION['first_name'] = ( ! $api_upgrade )
                                ? ( ( !empty( $name_data ) && isset( $name_data['given_name'] ) ) ? $name_data['given_name'] : '' )
                                : ( isset( $PayPalResult['FIRSTNAME'] ) ? $PayPalResult['FIRSTNAME'] : '' );
        $_SESSION['last_name'] = ( ! $api_upgrade )
                                ? ( ( !empty( $name_data ) && isset( $name_data['surname'] ) ) ? $name_data['surname'] : '' )
                                : ( isset( $PayPalResult['LASTNAME'] ) ? $PayPalResult['LASTNAME'] : '' );
        $_SESSION['RecurringProfileId'] = ( ! $api_upgrade )
                                        ? ( ( !empty( $full_response ) && isset( $full_response['id'] ) ) ? $full_response['id'] : '' )
                                        : ( isset( $PayPalResult['PROFILEID'] ) ? $PayPalResult['PROFILEID'] : '' );

        header('Location: order-complete.php');
} else {
        $_SESSION['paypal_errors'] = $PayPalResult['ERRORS'];
        header('Location: ../error.php');
}

