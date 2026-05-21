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
    'MerchantID' => $rest_merchant_id,
	'PrintHeaders' => $print_headers,
	'LogResults' => $log_results,
	'LogPath' => $log_path,
);

$PayPal = new angelleye\PayPal\PayPalREST($PayPalConfig);

/**
 * Amount breakdown required for Pay Later and order validation
 */
$breakdown = [
    'item_total' => [
        'currency_code' => '',                       // Currency for the combined items
        'value' => ''                              // Total cost of all items before shipping/tax
    ],
    'shipping' => [
        'currency_code' => '',                       // Currency for the combined items
        'value' => ''                              // Cost of shipping
    ],
    'handling' => [
        'currency_code' => '',                       // Currency for the combined items
        'value' => ''                               // Cost of handling fees
    ],
    'tax_total' => [
        'currency_code' => '',                       // Currency for the combined items
        'value' => ''                               // Total tax amount
    ]
];

// Initialize an empty list to store individual products
$orderItems = [];

// Define the details for the first product
$item = [
    'name' => '',                       // Name of the product
    'description' => '',                // Product description
    'quantity' => '',                   // Number of units
    'category' => '',                   // Category of item
    'unit_amount' => [
        'currency_code' => '',          // Currency per unit
        'value' => ''                   // Price per single unit
    ] 
];
// Add the first item to the order items array
array_push($orderItems, $item);

// Define the details for the second product
$item = [
    'name' => '',                       // Name of the product
    'description' => '',                // Product description
    'quantity' => '',                   // Number of units
    'category' => '',                   // Category of item
    'unit_amount' => [
        'currency_code' => '',          // Currency per unit
        'value' => ''                   // Price per single unit
    ] 
];
// Add the second item to the order items array
array_push($orderItems, $item);

$createOrderPayload = [
    'intent' => 'CAPTURE',                                  // Set to capture funds immediately after authorization
    'purchase_units' => [
        [
            'description' => '',                            // Overall description of the purchase 
            'amount' => [
                'currency_code' => '',                      // Final currency
                'value' => '',                              // Final total price (items + shipping + tax)
                'breakdown' => $breakdown,                  // Reference the cost breakdown defined earlier
            ],
            'items' => $orderItems,                         // Reference the list of items created earlier
        ],
    ],
    'application_context' => [
        'return_url' => '',             // URL after successful payment
        'cancel_url' => '',             // URL if user cancels
        'brand_name' => '',             // The business name shown on PayPal
        'user_action' => '',            // Label for the final button on PayPal
    ]
];

// Execute the API call to create the order with PayPal
$PayPalResult = $PayPal->createOrder($createOrderPayload);

// Output a clickable link to redirect the user to PayPal for approval
echo '<a href="' . $PayPalResult['approval_url'] . '">Click here to continue.</a><br /><br />';

// Print the full API response for debugging purposes
echo '<pre>';
print_r($PayPalResult);