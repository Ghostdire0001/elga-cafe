<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/order-functions.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waiter') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;
$payment_method = $data['payment_method'] ?? 'paid_cash';

if(markOrderPaid($pdo, $order_id, $payment_method)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark order as paid']);
}
?>
