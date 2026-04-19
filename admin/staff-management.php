<?php
$page_title = 'Staff & Order Management';
require_once '../includes/config.php';
require_once '../includes/translations.php';
require_once '../includes/theme.php';
require_once '../includes/language.php';

// Check if user has admin or manager access
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

$current_lang = getCurrentLanguage();
$current_theme = getCurrentTheme();
$message = '';
$error = '';

// ============================================
// WAITER CRUD OPERATIONS
// ============================================

// Handle Add/Edit Waiter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add New Waiter
        if ($_POST['action'] === 'add_waiter') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $assigned_tables = implode(',', $_POST['assigned_tables'] ?? []);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            try {
                // Check if username exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "Username already exists!";
                } else {
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name, is_active) VALUES (?, ?, ?, 'waiter', ?, ?)");
                    $stmt->execute([$username, $email, $password, $full_name, $is_active]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert waiter assignment
                    $stmt = $pdo->prepare("INSERT INTO waiters (user_id, assigned_tables, is_active) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $assigned_tables, $is_active]);
                    
                    $message = "Waiter added successfully!";
                }
            } catch (Exception $e) {
                $error = "Error adding waiter: " . $e->getMessage();
            }
        }
        
        // Edit Waiter
        elseif ($_POST['action'] === 'edit_waiter') {
            $waiter_id = intval($_POST['waiter_id']);
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $assigned_tables = implode(',', $_POST['assigned_tables'] ?? []);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            try {
                // Update user
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $full_name, $is_active, $user_id]);
                
                // Update password if provided
                if (!empty($_POST['new_password'])) {
                    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $user_id]);
                }
                
                // Update waiter assignment
                $stmt = $pdo->prepare("UPDATE waiters SET assigned_tables = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$assigned_tables, $is_active, $waiter_id]);
                
                $message = "Waiter updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating waiter: " . $e->getMessage();
            }
        }
        
        // Delete Waiter
        elseif ($_POST['action'] === 'delete_waiter') {
            $waiter_id = intval($_POST['waiter_id']);
            $user_id = intval($_POST['user_id']);
            
            try {
                // Delete from waiters table first
                $stmt = $pdo->prepare("DELETE FROM waiters WHERE id = ?");
                $stmt->execute([$waiter_id]);
                
                // Delete from users table
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $message = "Waiter deleted successfully!";
            } catch (Exception $e) {
                $error = "Error deleting waiter: " . $e->getMessage();
            }
        }
    }
}

// Get all waiters
$waiters = $pdo->query("
    SELECT w.*, u.username, u.email, u.full_name, u.is_active as user_active 
    FROM waiters w 
    JOIN users u ON w.user_id = u.id 
    WHERE u.role = 'waiter' 
    ORDER BY u.full_name
")->fetchAll();

// Get all tables (1-20)
$all_tables = [];
for ($i = 1; $i <= 20; $i++) {
    $all_tables[] = 'TABLE_' . str_pad($i, 2, '0', STR_PAD_LEFT);
}

// ============================================
// ORDER ANALYTICS & FILTERING
// ============================================

// Get filter parameters
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';
$waiter_filter = isset($_GET['waiter_filter']) ? intval($_GET['waiter_filter']) : 0;
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$table_filter = isset($_GET['table_filter']) ? $_GET['table_filter'] : '';

// Build date range
$start_date = '';
$end_date = '';
switch ($date_filter) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'this_month':
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        break;
    case 'custom':
        if (isset($_GET['custom_start']) && isset($_GET['custom_end'])) {
            $start_date = $_GET['custom_start'] . ' 00:00:00';
            $end_date = $_GET['custom_end'] . ' 23:59:59';
        }
        break;
}

// Build orders query
$sql = "SELECT o.*, 
        u.username as waiter_username, 
        u.full_name as waiter_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
        FROM orders o 
        LEFT JOIN waiters w ON o.waiter_id = w.id 
        LEFT JOIN users u ON w.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($start_date && $end_date) {
    $sql .= " AND o.created_at BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

if ($waiter_filter > 0) {
    $sql .= " AND o.waiter_id = ?";
    $params[] = $waiter_filter;
}

if ($status_filter !== 'all') {
    $sql .= " AND o.request_status = ?";
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

// Calculate summary stats
$total_orders = count($orders);
$total_revenue = array_sum(array_column($orders, 'total_amount'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Orders by waiter
$orders_by_waiter = [];
foreach ($orders as $order) {
    $waiter_name = $order['waiter_name'] ?? 'Unassigned';
    if (!isset($orders_by_waiter[$waiter_name])) {
        $orders_by_waiter[$waiter_name] = 0;
    }
    $orders_by_waiter[$waiter_name]++;
}

require_once 'includes/header.php';
?>

<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        max-width: 500px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
    }
    @media (max-width: 640px) {
        .modal-content {
            width: 95%;
            margin: 1rem;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-users"></i> Staff & Order Management</h1>
    <button onclick="openAddWaiterModal()" class="btn-success">
        <i class="fas fa-plus"></i> Add New Waiter
    </button>
</div>

<?php if($message): ?>
    <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- ============================================ -->
<!-- WAITERS SECTION -->
<!-- ============================================ -->
<div class="form-container mb-6">
    <h2 class="text-xl font-bold mb-4"><i class="fas fa-user-clock text-orange-custom"></i> Waiters Management</h2>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Assigned Tables</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($waiters)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fas fa-user-slash text-4xl mb-2 block"></i>
                            No waiters found. Click "Add New Waiter" to create one.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach($waiters as $waiter): 
                    $assigned_tables_list = explode(',', $waiter['assigned_tables'] ?? '');
                    $tables_display = [];
                    foreach ($assigned_tables_list as $t) {
                        if ($t) $tables_display[] = str_replace('TABLE_', 'Table ', $t);
                    }
                ?>
                    <tr>
                        <td><?php echo $waiter['id']; ?></td>
                        <td class="font-medium"><?php echo htmlspecialchars($waiter['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($waiter['username']); ?></td>
                        <td><?php echo htmlspecialchars($waiter['email']); ?></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach($tables_display as $table): ?>
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo $table; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <span class="<?php echo $waiter['user_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $waiter['user_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button onclick="editWaiter(<?php echo htmlspecialchars(json_encode($waiter)); ?>)" 
                                    class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteWaiter(<?php echo $waiter['id']; ?>, <?php echo $waiter['user_id']; ?>)" 
                                    class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- ORDER ANALYTICS SECTION -->
<!-- ============================================ -->
<div class="form-container mb-6">
    <h2 class="text-xl font-bold mb-4"><i class="fas fa-chart-line text-orange-custom"></i> Order Analytics</h2>
    
    <!-- Summary Stats -->
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Orders</p>
                    <p class="stat-value"><?php echo $total_orders; ?></p>
                </div>
                <i class="fas fa-shopping-cart text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Revenue</p>
                    <p class="stat-value">$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
                <i class="fas fa-dollar-sign text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Avg Order Value</p>
                    <p class="stat-value">$<?php echo number_format($avg_order_value, 2); ?></p>
                </div>
                <i class="fas fa-chart-line text-3xl text-orange-custom"></i>
            </div>
        </div>
    </div>
    
    <!-- Orders by Waiter -->
    <?php if (!empty($orders_by_waiter)): ?>
    <div class="mb-6 p-4 bg-gray-50 rounded-lg" style="background-color: var(--bg-secondary);">
        <h3 class="font-semibold mb-3"><i class="fas fa-chart-pie"></i> Orders by Waiter</h3>
        <div class="flex flex-wrap gap-4">
            <?php foreach($orders_by_waiter as $waiter_name => $count): ?>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-custom"><?php echo $count; ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($waiter_name); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <form method="GET" action="" class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Date Range</label>
                <select name="date_filter" class="form-select" onchange="toggleCustomDate(this.value)">
                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo $date_filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="this_week" <?php echo $date_filter == 'this_week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>
            <div id="custom-dates" style="display: <?php echo $date_filter == 'custom' ? 'flex' : 'none'; ?>" class="gap-2 col-span-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">Start Date</label>
                    <input type="date" name="custom_start" class="form-input" value="<?php echo explode(' ', $start_date)[0] ?? ''; ?>">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">End Date</label>
                    <input type="date" name="custom_end" class="form-input" value="<?php echo explode(' ', $end_date)[0] ?? ''; ?>">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Waiter</label>
                <select name="waiter_filter" class="form-select">
                    <option value="0">All Waiters</option>
                    <?php foreach($waiters as $waiter): ?>
                        <option value="<?php echo $waiter['id']; ?>" <?php echo $waiter_filter == $waiter['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($waiter['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status_filter" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="requested" <?php echo $status_filter == 'requested' ? 'selected' : ''; ?>>Requested</option>
                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">&nbsp;</label>
                <button type="submit" class="btn-primary w-full">Apply Filters</button>
            </div>
        </div>
    </form>
    
    <!-- Orders Table -->
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Table</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Waiter</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No orders found for the selected filters.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo str_replace('TABLE_', 'Table ', $order['table_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                        <td><?php echo $order['item_count']; ?> items</td>
                        <td class="font-bold text-orange-custom">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['waiter_name'] ?? 'Unassigned'); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = ucfirst($order['request_status'] ?? 'Unknown');
                            switch ($order['request_status']) {
                                case 'requested': $status_class = 'badge-warning'; break;
                                case 'confirmed': $status_class = 'badge-success'; break;
                                case 'rejected': $status_class = 'badge-danger'; break;
                                case 'completed': $status_class = 'badge-success'; break;
                                default: $status_class = 'badge-secondary';
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                            <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" 
                                    class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- MODALS -->
<!-- ============================================ -->

<!-- Add/Edit Waiter Modal -->
<div id="waiterModal" class="modal">
    <div class="modal-content bg-white rounded-lg shadow-xl p-6" style="background-color: var(--card-bg);">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modalTitle">Add New Waiter</h2>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add_waiter">
            <input type="hidden" name="waiter_id" id="waiter_id">
            <input type="hidden" name="user_id" id="user_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Full Name *</label>
                    <input type="text" name="full_name" id="full_name" required class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Username *</label>
                    <input type="text" name="username" id="username" required class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email *</label>
                    <input type="email" name="email" id="email" required class="form-input w-full">
                </div>
                <div id="password_field">
                    <label class="block text-sm font-medium mb-1">Password *</label>
                    <input type="password" name="password" id="password" class="form-input w-full">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>
                <div id="new_password_field" style="display: none;">
                    <label class="block text-sm font-medium mb-1">New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password" id="new_password" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Assigned Tables</label>
                    <div class="grid grid-cols-4 gap-2 max-h-40 overflow-y-auto p-2 border rounded">
                        <?php foreach($all_tables as $table): ?>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="assigned_tables[]" value="<?php echo $table; ?>" class="assigned-table-checkbox mr-1">
                                <span class="text-sm"><?php echo str_replace('TABLE_', 'Table ', $table); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" checked class="mr-2">
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn-primary">Save Waiter</button>
                <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content bg-white rounded-lg shadow-xl p-6" style="background-color: var(--card-bg); max-width: 600px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Order Details</h2>
            <button onclick="closeOrderModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <div id="orderDetailsContent">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    // Custom date toggle
    function toggleCustomDate(value) {
        const customDiv = document.getElementById('custom-dates');
        customDiv.style.display = value === 'custom' ? 'flex' : 'none';
    }
    
    // Waiter CRUD functions
    function openAddWaiterModal() {
        document.getElementById('modalTitle').innerText = 'Add New Waiter';
        document.getElementById('formAction').value = 'add_waiter';
        document.getElementById('waiter_id').value = '';
        document.getElementById('user_id').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('username').value = '';
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('password_field').style.display = 'block';
        document.getElementById('new_password_field').style.display = 'none';
        document.getElementById('is_active').checked = true;
        document.querySelectorAll('.assigned-table-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('waiterModal').classList.add('active');
    }
    
    function editWaiter(waiter) {
        document.getElementById('modalTitle').innerText = 'Edit Waiter';
        document.getElementById('formAction').value = 'edit_waiter';
        document.getElementById('waiter_id').value = waiter.id;
        document.getElementById('user_id').value = waiter.user_id;
        document.getElementById('full_name').value = waiter.full_name;
        document.getElementById('username').value = waiter.username;
        document.getElementById('email').value = waiter.email;
        document.getElementById('password').required = false;
        document.getElementById('password_field').style.display = 'none';
        document.getElementById('new_password_field').style.display = 'block';
        document.getElementById('is_active').checked = waiter.user_active == 1;
        
        // Set assigned tables
        const assignedTables = waiter.assigned_tables ? waiter.assigned_tables.split(',') : [];
        document.querySelectorAll('.assigned-table-checkbox').forEach(cb => {
            cb.checked = assignedTables.includes(cb.value);
        });
        
        document.getElementById('waiterModal').classList.add('active');
    }
    
    function deleteWaiter(waiterId, userId) {
        if (confirm('Are you sure you want to delete this waiter? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_waiter">
                <input type="hidden" name="waiter_id" value="${waiterId}">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function closeModal() {
        document.getElementById('waiterModal').classList.remove('active');
    }
    
    // Order details
    function viewOrderDetails(orderId) {
        fetch(`/get-order-details.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let itemsHtml = '';
                    data.order.items.forEach(item => {
                        itemsHtml += `
                            <div class="flex justify-between py-2 border-b">
                                <span>${item.quantity}x ${item.meal_name}</span>
                                <span>$${(item.quantity * item.unit_price).toFixed(2)}</span>
                            </div>
                        `;
                    });
                    
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="font-semibold">Order #:</span>
                                <span>${data.order.id}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Table:</span>
                                <span>${data.order.table_number ? data.order.table_number.replace('TABLE_', 'Table ') : 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Customer:</span>
                                <span>${data.order.customer_name || 'Guest'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Waiter:</span>
                                <span>${data.order.waiter_name || 'Unassigned'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Status:</span>
                                <span>${data.order.request_status || 'Unknown'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Payment:</span>
                                <span>${data.order.payment_status || 'pending'}</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="font-semibold mb-2">Items:</div>
                                ${itemsHtml || '<p>No items</p>'}
                            </div>
                            <div class="flex justify-between pt-2 border-t font-bold">
                                <span>Total:</span>
                                <span class="text-orange-custom">$${parseFloat(data.order.total_amount).toFixed(2)}</span>
                            </div>
                            <div class="text-sm text-gray-500 pt-2">
                                <i class="far fa-clock"></i> ${new Date(data.order.created_at).toLocaleString()}
                            </div>
                        </div>
                    `;
                    document.getElementById('orderModal').classList.add('active');
                } else {
                    alert('Error loading order details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order details');
            });
    }
    
    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('active');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('waiterModal');
        const orderModal = document.getElementById('orderModal');
        if (event.target === modal) closeModal();
        if (event.target === orderModal) closeOrderModal();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
