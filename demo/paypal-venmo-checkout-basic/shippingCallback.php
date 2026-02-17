<?php

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
}

header('Content-Type: application/json');

// Read PayPal request
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// Extract item total safely
$item_total = isset($data['purchase_units'][0]['amount']['breakdown']['item_total']['value'])
    ? (float)$data['purchase_units'][0]['amount']['breakdown']['item_total']['value']
    : 0.00;

// Your updated costs
$shipping_cost = 12.00;
$handling_fee  = 4.50;
$tax_total     = 3.50;

// Calculate new grand total
$grand_total = $item_total + $shipping_cost + $handling_fee + $tax_total;

// Build response
$response = [
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "USD",
            "value" => number_format($grand_total, 2, '.', ''),
            "breakdown" => [
                "item_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($item_total, 2, '.', '')
                ],
                "shipping" => [
                    "currency_code" => "USD",
                    "value" => number_format($shipping_cost, 2, '.', '')
                ],
                "handling" => [
                    "currency_code" => "USD",
                    "value" => number_format($handling_fee, 2, '.', '')
                ],
                "tax_total" => [
                    "currency_code" => "USD",
                    "value" => number_format($tax_total, 2, '.', '')
                ]
            ]
        ],

        // IMPORTANT: Shipping options required
        "shipping" => [
            "options" => [[
                "id" => "SHIP_1",
                "label" => "Standard Shipping",
                "type" => "SHIPPING",
                "selected" => true,
                "amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($shipping_cost, 2, '.', '')
                ]
            ]]
        ]
    ]]
];

echo json_encode($response);
exit;