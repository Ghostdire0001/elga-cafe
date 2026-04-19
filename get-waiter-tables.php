<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waiter') {
    echo json_encode(['tables' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT w.assigned_tables FROM waiters w WHERE w.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$waiter = $stmt->fetch();

$tables = $waiter ? explode(',', $waiter['assigned_tables']) : [];
echo json_encode(['tables' => $tables]);
?>
