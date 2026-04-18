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
$customer_name = isset($data['customer_name']) ? $data['customer_name'] : 'Guest';
$subtotal = $data['subtotal'];

$order_id = placeOrder($pdo, $table_number, $customer_name, $cart_items, $subtotal);

if ($order_id) {
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to place order']);
}
?>
