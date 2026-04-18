<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/order-functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart']) || !isset($data['table'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$table_number = $data['table'];
$cart_items = $data['cart'];
$subtotal = array_reduce($cart_items, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

saveCartToDatabase($pdo, $table_number, session_id(), $cart_items, $subtotal);

echo json_encode(['success' => true]);
?>
