<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/order-functions.php';

header('Content-Type: application/json');

$table_number = isset($_GET['table']) ? $_GET['table'] : null;

if (!$table_number) {
    echo json_encode(['success' => false, 'cart' => []]);
    exit;
}

$cart_data = getCartFromDatabase($pdo, $table_number, session_id());

echo json_encode(['success' => true, 'cart' => $cart_data['items']]);
?>
