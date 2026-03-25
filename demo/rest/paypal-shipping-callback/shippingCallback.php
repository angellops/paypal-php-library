<?php
require_once('../../../includes/config.php');

// Optional safety check
if (isset($api_mode) && $api_mode === 'classic') {
    header('Location: ../../');
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

$order_id = isset($data['id']) ? $data['id'] : null;
$reference_id = isset($data['purchase_units'][0]['reference_id']) ? $data['purchase_units'][0]['reference_id'] : '';

$items = [];
$data_items = $data['purchase_units'][0]['items'];
if (isset($data_items) && is_array($data_items)) {
    foreach ($data_items as $item) {
        $items[] = [
            "name" => isset($item['name']) ? $item['name'] : '',
            "unit_amount" => [
                "currency_code" => isset($item['unit_amount']['currency_code']) ? $item['unit_amount']['currency_code'] : 'USD',
                "value" => isset($item['unit_amount']['value']) ? $item['unit_amount']['value'] : '0.00'
            ],
            "quantity" => isset($item['quantity']) ? $item['quantity'] : "1",
            "sku" => isset($item['sku']) ? $item['sku'] : ""
        ];
    }
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
    "id" => $order_id,
    "purchase_units" => [[
        "reference_id" => $reference_id,
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
        "shipping_options" => [[
            "id" => "SHIP_1",
            "label" => "Standard Shipping",
            "type" => "SHIPPING",
            "selected" => true,
            "amount" => [
                "currency_code" => "USD",
                "value" => number_format($shipping_cost, 2, '.', '')
            ]
        ]]
    ]]
];

echo json_encode($response);
exit;