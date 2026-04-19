<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
    exit;
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username as waiter_username, u.full_name as waiter_name
    FROM orders o
    LEFT JOIN waiters w ON o.waiter_id = w.id
    LEFT JOIN users u ON w.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, m.name as meal_name
    FROM order_items oi
    JOIN meals m ON oi.meal_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order['items'] = $stmt->fetchAll();

echo json_encode(['success' => true, 'order' => $order]);
?>
