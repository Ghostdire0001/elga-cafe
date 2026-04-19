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

$stmt = $pdo->prepare("SELECT w.id FROM waiters w WHERE w.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$waiter = $stmt->fetch();

if(confirmOrder($pdo, $order_id, $waiter['id'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to confirm order']);
}
?>
