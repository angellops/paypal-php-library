<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

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

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

// Validate API mode
$PayPal->ValidateMode('rest');

/**
 * Amount breakdown required for Pay Later and order validation
 */
$breakdown = [
    'item_total' => [
        'currency_code' => 'USD',                       // Currency for the combined items
        'value' => '80.00'                              // Total cost of all items before shipping/tax
    ],
    'shipping' => [
        'currency_code' => 'USD',                       // Currency for the combined items
        'value' => '10.00'                              // Cost of shipping
    ],
    'handling' => [
        'currency_code' => 'USD',                       // Currency for the combined items
        'value' => '5.00'                               // Cost of handling fees
    ],
    'tax_total' => [
        'currency_code' => 'USD',                       // Currency for the combined items
        'value' => '5.00'                               // Total tax amount
    ]
];

// Initialize an empty list to store individual products
$orderItems = [];

// Define the details for the first product
$item = [
    'name' => 'Widget 123',                 // Name of the product
    'description' => 'Widget 123',          // Product description
    'quantity' => '1',                      // Number of units
    'unit_amount' => [
        'currency_code' => 'USD',           // Currency per unit
        'value' => '40.00'                  // Price per single unit
    ] 
];
// Add the first item to the order items array
array_push($orderItems, $item);

// Define the details for the second product
$item = [
    'name' => 'Widget 456',                 // Name of the product
    'description' => 'Widget 456',          // Product description
    'quantity' => '1',                      // Number of units
    'unit_amount' => [
        'currency_code' => 'USD',           // Currency per unit
        'value' => '40.00'                  // Price per single unit
    ] 
];
// Add the second item to the order items array
array_push($orderItems, $item);

$createOrderPayload = [
    'intent' => 'CAPTURE',                                  // Set to capture funds immediately after authorization
    'purchase_units' => [
        [
            'description' => 'This is a test order',        // Overall description of the purchase 
            'amount' => [
                'currency_code' => 'USD',                   // Final currency
                'value' => '100.00',                        // Final total price (items + shipping + tax)
                'breakdown' => $breakdown,                  // Reference the cost breakdown defined earlier
            ],
            'items' => $orderItems,                         // Reference the list of items created earlier
        ],
    ],
    'payment_source' => [
        'paypal' => [
            'experience_context' => [
                'brand_name' => 'AngellEYE',                                    // The business name shown on PayPal
                'landing_page' => 'LOGIN',                                      // Direct user to login or guest checkout
                'user_action' => 'CONTINUE',                                    // Label for the final button on PayPal
                'shipping_preference' => 'NO_SHIPPING',                         // Disable shipping address collection  
                'return_url' => $domain . 'samples/rest/captureOrder.php',      // URL after successful payment
                'cancel_url' => $domain . 'samples/rest/createOrder.php'        // URL if user cancels
            ]
        ]
    ]
];

// Execute the API call to create the order with PayPal
$PayPalResult = $PayPal->createOrder($createOrderPayload);

// Output a clickable link to redirect the user to PayPal for approval
echo '<a href="' . $PayPalResult['approval_url'] . '">Click here to continue.</a><br /><br />';

// Print the full API response for debugging purposes
echo '<pre>';
print_r($PayPalResult);