<?php
// Load dependencies and configuration
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

// Capture and decode the incoming JSON payload from the request body
$data = json_decode(file_get_contents('php://input'), true);

// Extract variables with null coalescing for safety
$id  = isset($data['id']) ? $data['id'] : null;
$qty = isset($data['qty']) ? (int)$data['qty'] : null;

/**
 * Validation Logic
 * Ensure an ID exists and quantity is a positive integer.
 */
if (!$id || $qty < 1) {
    http_response_code(400);
    exit;
}

/**
 * Session Update
 * Check if the item exists in the 'items' array within the session.
 * If found, update the quantity to the new value.
 */
if (isset($_SESSION['items'][$id])) {
    $_SESSION['items'][$id]['qty'] = $qty;
    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated'
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Item not found in cart'
    ]);
}