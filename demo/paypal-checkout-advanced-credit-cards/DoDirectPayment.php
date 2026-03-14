<?php
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
}

$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
	'PayPalAPIUpgrade' => $api_upgrade,
    'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret,
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

$cart = $_SESSION['shopping_cart'];
$payer = $_SESSION['payer'];
$billing = $_SESSION['billing'];
$card_details = $_SESSION['card_details'];
$line_items = [];
foreach ($cart['acdc_items'] as $item) {
    $line_items[] = [
        'name' => $item['name'],
        'description' => $item['desc'],
        'sku' => $item['id'],
        'product_code' => $item['id'],
        'commodity_code' => '86101700',
        'unit_of_measure' => 'UNIT',
        'quantity' => (string)$item['qty'],
        'category' => $item['category'],
        'unit_amount' => [
            'currency_code' => 'USD',
            'value' => number_format($item['price'], 2)
        ],
        'unit_tax_amount' => [
            'currency_code' => 'USD',
            'value' => number_format($item['tax'], 2)
        ],
        'unit_discount_amount' => [
            'currency_code' => 'USD',
            'value' => '0.00'
        ]
    ];
}

$orderPayload = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "reference_id" => uniqid("ORDER-"),
        "invoice_id" => uniqid("INV-"),
        "amount" => [
            "currency_code" => "USD",
            "value" => number_format($cart['grand_total'], 2),
            "breakdown" => [
                "item_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($cart['subtotal'], 2)
                ],
                "shipping" => [
                    "currency_code" => "USD",
                    "value" => number_format($cart['shipping'], 2)
                ],
                "handling" => [
                    "currency_code" => "USD",
                    "value" => number_format($cart['handling'], 2)
                ],
                "tax_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($cart['tax'], 2)
                ],
                "discount" => [
                    "currency_code" => "USD",
                    "value" => "0.00"
                ],
                "duty" => [
                    "currency_code" => "USD",
                    "value" => "0.00"
                ]
            ]
        ],
        "items" => $line_items,
        "shipping" => [
            "name" => [
                "full_name" => $payer['firstname'] . ' ' . $payer['lastname']
            ],
            "address" => [
                "address_line_1" => $billing['street'],
                "admin_area_2" => $billing['city'],
                "admin_area_1" => $billing['state'],
                "postal_code" => $billing['zip'],
                "country_code" => $billing['countrycode']
            ]
        ]
    ]],
    "payment_source" => [
        "card" => [
            "number" => $card_details['accountnumber'],
            "expiry" => $card_details['expiry'],
            "security_code" => $card_details['cvv'],
            "name" => $payer['firstname'] . ' ' . $payer['lastname'],
            "billing_address" => [
                "address_line_1" => $billing['street'],
                "admin_area_2" => $billing['city'],
                "admin_area_1" => $billing['state'],
                "postal_code" => $billing['zip'],
                "country_code" => $billing['countrycode']
            ],
            "attributes" => [
                "verification" => [
                    "method" => "SCA_ALWAYS"
                ]
            ],
            "vault" => [
                "store_in_vault" => "ON_SUCCESS"
            ],
            "experience_context" => [
                "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                "user_action" => "PAY_NOW"
            ]
        ]
    ]
];

$paypalRequestId = uniqid('pprid_', true);

$PayPalResult = $PayPal->createOrder($orderPayload, $paypalRequestId);

/**
 * Now we'll check for any errors returned by PayPal, and if we get an error,
 * we'll save the error details to a session and redirect the user to an
 * error page to display it accordingly.
 *
 * If the call is successful, we'll save some data we might want to use
 * later into session variables.
 */
if( $PayPalResult['success'] ) {
    /**
     * Here we'll pull out data from the PayPal response.
     */
    $orderID = isset($PayPalResult['full_response']['id']) ? $PayPalResult['full_response']['id'] : '';
    $getOrderResult = $PayPal->getOrder($orderID);

    if( $getOrderResult['success'] ) {
        $_SESSION['paypal_order_id'] = isset($getOrderResult['order']['id']) ? $getOrderResult['order']['id'] : '';
        $purchaseUnit = $getOrderResult['order']['purchase_units'][0];
        $shipping = isset( $purchaseUnit['shipping'] ) ? $purchaseUnit['shipping'] : [];
        $_SESSION['shipping_name'] = isset($shipping['name']['full_name']) ? $shipping['name']['full_name'] : '';
        $_SESSION['shipping_street'] = isset($shipping['address']['address_line_1']) ? $shipping['address']['address_line_1'] : '';
        $_SESSION['shipping_city'] = isset($shipping['address']['admin_area_2']) ? $shipping['address']['admin_area_2'] : '';
        $_SESSION['shipping_state'] = isset($shipping['address']['admin_area_1']) ? $shipping['address']['admin_area_1'] : '';
        $_SESSION['shipping_zip'] = isset($shipping['address']['postal_code']) ? $shipping['address']['postal_code'] : '';
        $_SESSION['shipping_country_code'] = isset($shipping['address']['country_code']) ? $shipping['address']['country_code'] : '';
    } else {
        $_SESSION['paypal_errors'] = $getOrderResult['errors'];
        header('Location: ../error.php');
    }

    header('Location: order-complete.php');
} else {
    $_SESSION['paypal_errors'] = $PayPalResult['errors'];
    header('Location: ../error.php');
}