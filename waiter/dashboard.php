<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/translations.php';
require_once '../includes/theme.php';
require_once '../includes/language.php';
require_once '../includes/order-functions.php';

// Check if user is logged in as waiter
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waiter') {
    header('Location: ../admin/login.php');
    exit();
}

$current_lang = getCurrentLanguage();
$current_theme = getCurrentTheme();

// Get waiter info
$stmt = $pdo->prepare("SELECT w.*, u.username, u.full_name FROM waiters w JOIN users u ON w.user_id = u.id WHERE w.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$waiter = $stmt->fetch();

// Get pending requests for this waiter's tables
$pending_requests = getPendingRequests($pdo, $waiter['id']);
$active_orders = getActiveOrders($pdo, $waiter['id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Waiter Dashboard - Elga Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
        }
        [data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --card-bg: #1f2937;
            --border-color: #374151;
        }
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .bg-orange-custom { background-color: #F97316; }
        .text-orange-custom { color: #F97316; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-preparing { background-color: #dbeafe; color: #1e40af; }
        .status-ready { background-color: #fed7aa; color: #92400e; }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-orange-custom text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold">Elga Cafe - Waiter Panel</h1>
                    <p class="text-orange-100 text-sm">Welcome, <?php echo htmlspecialchars($waiter['full_name']); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="../admin/logout.php" class="bg-white text-orange-custom px-4 py-1 rounded-lg text-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-6">
        <!-- Assigned Tables -->
        <div class="bg-white rounded-lg shadow p-4 mb-6" style="background-color: var(--card-bg);">
            <h2 class="font-bold mb-2"><i class="fas fa-table text-orange-custom"></i> Your Assigned Tables</h2>
            <div class="flex flex-wrap gap-2">
                <?php 
                $tables = explode(',', $waiter['assigned_tables']);
                foreach($tables as $table): ?>
                    <span class="px-3 py-1 bg-orange-100 text-orange-custom rounded-full text-sm">
                        <?php echo str_replace('TABLE_', 'Table ', $table); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending Requests Section -->
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-clock text-orange-custom"></i> Pending Requests
            <?php if(count($pending_requests) > 0): ?>
                <span class="bg-red-500 text-white text-sm px-2 py-1 rounded-full ml-2"><?php echo count($pending_requests); ?></span>
            <?php endif; ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <?php if(empty($pending_requests)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center col-span-full" style="background-color: var(--card-bg);">
                    <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                    <p class="text-gray-500">No pending requests</p>
                </div>
            <?php endif; ?>
            
            <?php foreach($pending_requests as $request): 
                $details = getOrderDetails($pdo, $request['id']);
            ?>
                <div class="bg-white rounded-lg shadow overflow-hidden" style="background-color: var(--card-bg);">
                    <div class="p-4 border-b" style="border-color: var(--border-color);">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-lg">
                                <?php echo str_replace('TABLE_', 'Table ', $request['table_number']); ?>
                            </h3>
                            <span class="status-pending px-2 py-1 rounded text-xs font-semibold">
                                <i class="fas fa-hourglass-half"></i> Pending
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($request['created_at'])); ?>
                        </p>
                    </div>
                    <div class="p-4">
                        <?php foreach($details['items'] as $item): ?>
                            <div class="flex justify-between text-sm mb-1">
                                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['meal_name']); ?></span>
                                <span>$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="border-t mt-2 pt-2 flex justify-between font-bold">
                            <span>Total:</span>
                            <span class="text-orange-custom">$<?php echo number_format($request['total_amount'], 2); ?></span>
                        </div>
                        <?php if($request['customer_name']): ?>
                            <p class="text-sm text-gray-500 mt-2"><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['customer_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 bg-gray-50 flex gap-2" style="background-color: var(--bg-secondary);">
                        <button onclick="confirmOrder(<?php echo $request['id']; ?>)" class="flex-1 bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
                            <i class="fas fa-check"></i> Confirm
                        </button>
                        <button onclick="rejectOrder(<?php echo $request['id']; ?>)" class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Active Orders Section -->
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-fire text-orange-custom"></i> Active Orders
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if(empty($active_orders)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center col-span-full" style="background-color: var(--card-bg);">
                    <i class="fas fa-coffee text-5xl text-gray-400 mb-3"></i>
                    <p class="text-gray-500">No active orders</p>
                </div>
            <?php endif; ?>
            
            <?php foreach($active_orders as $order): 
                $details = getOrderDetails($pdo, $order['id']);
            ?>
                <div class="bg-white rounded-lg shadow overflow-hidden" style="background-color: var(--card-bg);">
                    <div class="p-4 border-b" style="border-color: var(--border-color);">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-lg">
                                <?php echo str_replace('TABLE_', 'Table ', $order['table_number']); ?>
                            </h3>
                            <span class="status-<?php echo $order['status']; ?> px-2 py-1 rounded text-xs font-semibold">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i> Confirmed at <?php echo date('h:i A', strtotime($order['confirmed_at'])); ?>
                        </p>
                    </div>
                    <div class="p-4">
                        <?php foreach($details['items'] as $item): ?>
                            <div class="flex justify-between text-sm mb-1">
                                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['meal_name']); ?></span>
                                <span>$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="border-t mt-2 pt-2 flex justify-between font-bold">
                            <span>Total:</span>
                            <span class="text-orange-custom">$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 flex gap-2" style="background-color: var(--bg-secondary);">
                        <button onclick="markPaid(<?php echo $order['id']; ?>)" class="flex-1 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition">
                            <i class="fas fa-money-bill"></i> Mark Paid
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function confirmOrder(orderId) {
            if(confirm('Confirm this order? It will be sent to the kitchen.')) {
                fetch('/waiter/confirm-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function rejectOrder(orderId) {
            let reason = prompt('Reason for rejection:');
            if(reason) {
                fetch('/waiter/reject-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, reason: reason })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function markPaid(orderId) {
            let method = confirm('Mark as paid? Click OK for Cash, Cancel for Card');
            let paymentMethod = method ? 'paid_cash' : 'paid_card';
            
            fetch('/waiter/mark-paid.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, payment_method: paymentMethod })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
