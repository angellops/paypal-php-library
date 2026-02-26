<?php
require_once('../../includes/config.php');

// Optional safety check
if (isset($api_mode) && $api_mode === 'classic') {
    header('Location: ./');
    exit;
}

header('Content-Type: application/json');

// Read PayPal request
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// Basic validation
if (!$data || !isset($data['purchase_units'][0])) {
    http_response_code(422);
    echo json_encode([
        "error" => "Invalid request payload"
    ]);
    exit;
}

// Extract item total safely
$item_total = isset($data['purchase_units'][0]['amount']['breakdown']['item_total']['value'])
    ? (float)$data['purchase_units'][0]['amount']['breakdown']['item_total']['value']
    : 0.00;

// Reject if total is 0
if ($item_total <= 0) {
    http_response_code(422);
    echo json_encode([
        "error" => "Invalid item total"
    ]);
    exit;
}

// Costs
$shipping_cost = 12.00;
$handling_fee  = 4.50;
$tax_total     = 3.50;

$grand_total = $item_total + $shipping_cost + $handling_fee + $tax_total;

http_response_code(200);

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