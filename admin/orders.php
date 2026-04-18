<?php
$page_title = 'Manage Orders';
require_once '../includes/config.php';
require_once '../includes/translations.php';
require_once '../includes/theme.php';
require_once '../includes/language.php';

$current_lang = getCurrentLanguage();
$current_theme = getCurrentTheme();

// Check if user has access (admin or manager)
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $message = "Order #$order_id status updated to " . ucfirst($new_status);
    }
}

// Get filter parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$table_filter = isset($_GET['table']) ? $_GET['table'] : '';

// Build query
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
        FROM orders o 
        WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}
if ($table_filter) {
    $sql .= " AND o.table_number = ?";
    $params[] = $table_filter;
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get unique table numbers for filter
$tables = $pdo->query("SELECT DISTINCT table_number FROM orders WHERE table_number IS NOT NULL ORDER BY table_number")->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🍽️ Manage Orders</h1>
    <div class="flex gap-2">
        <button onclick="window.print()" class="btn-secondary">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<?php if(isset($message)): ?>
    <div class="alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="filters-container p-4 mb-6">
    <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-sm font-medium mb-1">Status Filter</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Orders</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-1">Table Filter</label>
            <select name="table" class="form-select" onchange="this.form.submit()">
                <option value="">All Tables</option>
                <?php foreach($tables as $table): ?>
                    <option value="<?php echo $table['table_number']; ?>" <?php echo $table_filter == $table['table_number'] ? 'selected' : ''; ?>>
                        <?php echo str_replace('TABLE_', 'Table ', $table['table_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-1">&nbsp;</label>
            <a href="orders.php" class="btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>

<!-- Orders List -->
<div class="space-y-4">
    <?php if(empty($orders)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center" style="background-color: var(--card-bg);">
            <i class="fas fa-shopping-cart text-5xl text-gray-400 mb-3"></i>
            <p class="text-gray-500">No orders found</p>
        </div>
    <?php endif; ?>
    
    <?php foreach($orders as $order): 
        // Get order items
        $stmt = $pdo->prepare("SELECT oi.*, m.name as meal_name 
                               FROM order_items oi 
                               JOIN meals m ON oi.meal_id = m.id 
                               WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $items = $stmt->fetchAll();
        
        $status_colors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'preparing' => 'bg-purple-100 text-purple-800',
            'ready' => 'bg-green-100 text-green-800',
            'completed' => 'bg-gray-100 text-gray-800'
        ];
        $status_color = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800';
    ?>
        <div class="bg-white rounded-lg shadow overflow-hidden" style="background-color: var(--card-bg);">
            <!-- Order Header -->
            <div class="p-4 border-b flex flex-wrap justify-between items-center" style="border-color: var(--border-color);">
                <div>
                    <h2 class="text-lg font-bold">
                        Order #<?php echo $order['id']; ?>
                        <?php if($order['table_number']): ?>
                            <span class="text-orange-custom text-sm ml-2">
                                <i class="fas fa-table"></i> <?php echo str_replace('TABLE_', 'Table ', $order['table_number']); ?>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-sm text-gray-500">
                        <i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                    <span class="font-bold text-orange-custom text-lg">
                        $<?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="p-4">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500">
                            <th class="pb-2">Item</th>
                            <th class="pb-2">Qty</th>
                            <th class="pb-2">Price</th>
                            <th class="pb-2">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr class="border-t" style="border-color: var(--border-color);">
                            <td class="py-2"><?php echo htmlspecialchars($item['meal_name']); ?></td>
                            <td class="py-2"><?php echo $item['quantity']; ?></td>
                            <td class="py-2">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="py-2">$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if($order['customer_name']): ?>
                <div class="mt-3 text-sm text-gray-500">
                    <i class="fas fa-user"></i> Customer: <?php echo htmlspecialchars($order['customer_name']); ?>
                </div>
                <?php endif; ?>
                
                <?php if($order['special_instructions']): ?>
                <div class="mt-2 text-sm text-orange-custom">
                    <i class="fas fa-comment"></i> Notes: <?php echo htmlspecialchars($order['special_instructions']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Actions -->
            <div class="p-4 border-t bg-gray-50" style="background-color: var(--bg-secondary); border-color: var(--border-color);">
                <form method="POST" action="" class="flex gap-2">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <select name="status" class="form-select text-sm">
                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirm</option>
                        <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <button type="submit" name="update_status" class="btn-primary text-sm">
                        <i class="fas fa-sync-alt"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    @media print {
        .sidebar, .menu-toggle, .page-header button, .filters-container, .order-actions, .no-print {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .bg-gray-50 {
            background-color: white !important;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>
