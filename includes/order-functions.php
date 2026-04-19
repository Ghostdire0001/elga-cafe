<?php
// Order system functions with waiter gateway support

function getTableNumber() {
    if (isset($_GET['table'])) {
        $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['table']);
        setcookie('current_table', $table, time() + (86400 * 30), "/", "", false, true);
        $_SESSION['current_table'] = $table;
        return $table;
    }
    if (isset($_COOKIE['current_table'])) {
        return $_COOKIE['current_table'];
    }
    if (isset($_SESSION['current_table'])) {
        return $_SESSION['current_table'];
    }
    return null;
}

function getCurrentWaiter() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'waiter') {
        $stmt = $pdo->prepare("SELECT * FROM waiters WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

function isWaiterAssignedToTable($pdo, $waiter_id, $table_number) {
    $stmt = $pdo->prepare("SELECT assigned_tables FROM waiters WHERE id = ?");
    $stmt->execute([$waiter_id]);
    $waiter = $stmt->fetch();
    if ($waiter && $waiter['assigned_tables']) {
        $assigned = explode(',', $waiter['assigned_tables']);
        return in_array($table_number, $assigned);
    }
    return false;
}

function requestOrder($pdo, $table_number, $customer_name, $cart_items, $subtotal, $order_source = 'customer') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO orders (table_number, customer_name, total_amount, status, request_status, order_source, created_at) 
                               VALUES (?, ?, ?, 'pending', 'requested', ?, NOW())");
        $stmt->execute([$table_number, $customer_name, $subtotal, $order_source]);
        $order_id = $pdo->lastInsertId();
        
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, meal_id, quantity, unit_price) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM active_carts WHERE table_number = ? AND session_id = ?");
        $stmt->execute([$table_number, session_id()]);
        
        $pdo->commit();
        return $order_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order request failed: " . $e->getMessage());
        return false;
    }
}

function confirmOrder($pdo, $order_id, $waiter_id) {
    $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed', request_status = 'confirmed', waiter_id = ?, confirmed_at = NOW() WHERE id = ?");
    return $stmt->execute([$waiter_id, $order_id]);
}

function rejectOrder($pdo, $order_id, $reason) {
    $stmt = $pdo->prepare("UPDATE orders SET request_status = 'rejected', rejection_reason = ? WHERE id = ?");
    return $stmt->execute([$reason, $order_id]);
}

function markOrderPaid($pdo, $order_id, $payment_method) {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, status = 'completed' WHERE id = ?");
    return $stmt->execute([$payment_method, $order_id]);
}

function getPendingRequests($pdo, $waiter_id = null) {
    $sql = "SELECT o.*, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
            FROM orders o 
            WHERE o.request_status = 'requested' AND o.status = 'pending'";
    $params = [];
    
    if ($waiter_id) {
        $stmt = $pdo->prepare("SELECT assigned_tables FROM waiters WHERE id = ?");
        $stmt->execute([$waiter_id]);
        $waiter = $stmt->fetch();
        if ($waiter && $waiter['assigned_tables']) {
            $tables = explode(',', $waiter['assigned_tables']);
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $sql .= " AND o.table_number IN ($placeholders)";
            $params = $tables;
        }
    }
    
    $sql .= " ORDER BY o.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getActiveOrders($pdo, $waiter_id = null) {
    $sql = "SELECT o.*, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
            FROM orders o 
            WHERE o.request_status = 'confirmed' AND o.status IN ('confirmed', 'preparing', 'ready')";
    $params = [];
    
    if ($waiter_id) {
        $stmt = $pdo->prepare("SELECT assigned_tables FROM waiters WHERE id = ?");
        $stmt->execute([$waiter_id]);
        $waiter = $stmt->fetch();
        if ($waiter && $waiter['assigned_tables']) {
            $tables = explode(',', $waiter['assigned_tables']);
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $sql .= " AND o.table_number IN ($placeholders)";
            $params = $tables;
        }
    }
    
    $sql .= " ORDER BY o.confirmed_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOrderDetails($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT o.*, 
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
                           FROM orders o WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmt = $pdo->prepare("SELECT oi.*, m.name as meal_name 
                               FROM order_items oi 
                               JOIN meals m ON oi.meal_id = m.id 
                               WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order['items'] = $stmt->fetchAll();
    }
    
    return $order;
}

// HTML Components
function getWaiterOrderButtonHTML($meal_id, $meal_name, $meal_price) {
    return '
    <button class="waiter-add-to-cart-btn w-full mt-2 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2"
            data-meal-id="' . $meal_id . '"
            data-meal-name="' . htmlspecialchars($meal_name) . '"
            data-meal-price="' . $meal_price . '">
        <i class="fas fa-cart-plus"></i>
        Add to Order (Waiter Mode)
    </button>';
}

function getCustomerOrderButtonHTML($meal_id, $meal_name, $meal_price) {
    return '
    <button class="customer-add-to-cart-btn w-full mt-2 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2"
            data-meal-id="' . $meal_id . '"
            data-meal-name="' . htmlspecialchars($meal_name) . '"
            data-meal-price="' . $meal_price . '">
        <i class="fas fa-cart-plus"></i>
        Add to Order
    </button>';
}

function getWaiterSidebarHTML() {
    return '
    <div id="order-sidebar" class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300" style="background-color: var(--card-bg);">
        <div class="p-4 border-b" style="border-color: var(--border-color);">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Table Order</h2>
                <button onclick="toggleWaiterSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="mt-2">
                <label class="block text-sm font-medium mb-1">Select Table:</label>
                <select id="waiter-table-select" class="w-full p-2 border rounded-lg">
                    <option value="">-- Select Table --</option>
                </select>
            </div>
        </div>
        <div id="waiter-order-items" class="flex-1 overflow-y-auto p-4" style="max-height: calc(100vh - 280px);">
            <p class="text-gray-500 text-center py-8">No items added</p>
        </div>
        <div class="border-t p-4" style="border-color: var(--border-color);">
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Subtotal:</span>
                <span id="waiter-order-subtotal" class="font-bold text-orange-custom">$0.00</span>
            </div>
            <div class="mb-3">
                <input type="text" id="waiter-customer-name" placeholder="Customer name (optional)" 
                       class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button onclick="submitWaiterOrder()" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-check-circle"></i> Request Order
            </button>
        </div>
    </div>
    <div id="order-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleWaiterSidebar()"></div>';
}

function getWaiterOrderJavaScript() {
    return '
    <script>
        let waiterCart = [];
        let waiterMode = ' . (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'waiter' ? 'true' : 'false') . ';
        
        function toggleWaiterSidebar() {
            document.getElementById("order-sidebar").classList.toggle("translate-x-full");
            document.getElementById("order-overlay").classList.toggle("hidden");
        }
        
        function addToWaiterCart(mealId, mealName, mealPrice) {
            const existingItem = waiterCart.find(item => item.id === mealId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                waiterCart.push({
                    id: mealId,
                    name: mealName,
                    price: parseFloat(mealPrice),
                    quantity: 1
                });
            }
            updateWaiterCartDisplay();
        }
        
        function updateWaiterCartDisplay() {
            const container = document.getElementById("waiter-order-items");
            const subtotal = waiterCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            if (waiterCart.length === 0) {
                container.innerHTML = \'<p class="text-gray-500 text-center py-8">No items added</p>\';
                document.getElementById("waiter-order-subtotal").textContent = "$0.00";
                return;
            }
            
            document.getElementById("waiter-order-subtotal").textContent = "$" + subtotal.toFixed(2);
            
            container.innerHTML = waiterCart.map(item => `
                <div class="flex justify-between items-center mb-3 p-2 border rounded">
                    <div>
                        <p class="font-semibold">${item.name}</p>
                        <p class="text-sm text-gray-500">$${item.price.toFixed(2)} x ${item.quantity}</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="updateWaiterQuantity(${item.id}, ${item.quantity - 1})" class="text-red-500">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateWaiterQuantity(${item.id}, ${item.quantity + 1})" class="text-green-500">+</button>
                        <button onclick="removeFromWaiterCart(${item.id})" class="text-red-500 ml-2">×</button>
                    </div>
                </div>
            `).join("");
        }
        
        function updateWaiterQuantity(mealId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromWaiterCart(mealId);
                return;
            }
            const item = waiterCart.find(i => i.id === mealId);
            if (item) {
                item.quantity = newQuantity;
                updateWaiterCartDisplay();
            }
        }
        
        function removeFromWaiterCart(mealId) {
            waiterCart = waiterCart.filter(item => item.id !== mealId);
            updateWaiterCartDisplay();
        }
        
        function submitWaiterOrder() {
            const tableSelect = document.getElementById("waiter-table-select");
            const tableNumber = tableSelect.value;
            const customerName = document.getElementById("waiter-customer-name").value;
            
            if (!tableNumber) {
                alert("Please select a table");
                return;
            }
            if (waiterCart.length === 0) {
                alert("Please add items to the order");
                return;
            }
            
            const subtotal = waiterCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            fetch("/place-order.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    cart: waiterCart,
                    table: tableNumber,
                    customer_name: customerName,
                    subtotal: subtotal,
                    order_source: "waiter"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Order requested for " + tableNumber.replace("TABLE_", "Table ") + "!");
                    waiterCart = [];
                    updateWaiterCartDisplay();
                    toggleWaiterSidebar();
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            });
        }
        
        // Load tables for waiter
        if (waiterMode) {
            fetch("/get-waiter-tables.php")
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById("waiter-table-select");
                    if (data.tables) {
                        data.tables.forEach(table => {
                            const option = document.createElement("option");
                            option.value = table;
                            option.textContent = table.replace("TABLE_", "Table ");
                            select.appendChild(option);
                        });
                    }
                });
        }
        
        // Event listeners for add to cart buttons
        document.addEventListener("DOMContentLoaded", function() {
            if (waiterMode) {
                document.querySelectorAll(".waiter-add-to-cart-btn").forEach(btn => {
                    btn.addEventListener("click", function() {
                        const mealId = parseInt(this.dataset.mealId);
                        const mealName = this.dataset.mealName;
                        const mealPrice = parseFloat(this.dataset.mealPrice);
                        addToWaiterCart(mealId, mealName, mealPrice);
                        toggleWaiterSidebar();
                    });
                });
            } else {
                document.querySelectorAll(".customer-add-to-cart-btn").forEach(btn => {
                    btn.addEventListener("click", function() {
                        const mealId = parseInt(this.dataset.mealId);
                        const mealName = this.dataset.mealName;
                        const mealPrice = parseFloat(this.dataset.mealPrice);
                        addToCustomerCart(mealId, mealName, mealPrice);
                        toggleCustomerSidebar();
                    });
                });
            }
        });
    </script>';
}
?>
