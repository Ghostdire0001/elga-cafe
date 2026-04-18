<?php
// Order system functions with table support

function getTableNumber() {
    // Get table number from URL parameter (from QR code)
    if (isset($_GET['table'])) {
        $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['table']);
        setcookie('current_table', $table, time() + (86400 * 30), "/", "", false, true);
        $_SESSION['current_table'] = $table;
        return $table;
    }
    
    // Then check cookie
    if (isset($_COOKIE['current_table'])) {
        return $_COOKIE['current_table'];
    }
    
    // Then check session
    if (isset($_SESSION['current_table'])) {
        return $_SESSION['current_table'];
    }
    
    return null;
}

function saveCartToDatabase($pdo, $table_number, $session_id, $cart_items, $subtotal) {
    $stmt = $pdo->prepare("INSERT INTO active_carts (table_number, session_id, items, subtotal, updated_at) 
                           VALUES (?, ?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE 
                           items = ?, subtotal = ?, updated_at = NOW()");
    $items_json = json_encode($cart_items);
    $stmt->execute([$table_number, $session_id, $items_json, $subtotal, $items_json, $subtotal]);
}

function getCartFromDatabase($pdo, $table_number, $session_id) {
    $stmt = $pdo->prepare("SELECT items, subtotal FROM active_carts 
                           WHERE table_number = ? AND session_id = ?");
    $stmt->execute([$table_number, $session_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        return [
            'items' => json_decode($result['items'], true),
            'subtotal' => floatval($result['subtotal'])
        ];
    }
    return ['items' => [], 'subtotal' => 0];
}

function placeOrder($pdo, $table_number, $customer_name, $cart_items, $subtotal) {
    try {
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (table_number, customer_name, total_amount, status, created_at) 
                               VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$table_number, $customer_name, $subtotal]);
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, meal_id, quantity, unit_price) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }
        
        // Clear the active cart
        $stmt = $pdo->prepare("DELETE FROM active_carts WHERE table_number = ? AND session_id = ?");
        $stmt->execute([$table_number, session_id()]);
        
        $pdo->commit();
        return $order_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order placement failed: " . $e->getMessage());
        return false;
    }
}

function getOrderButtonHTML($meal_id, $meal_name, $meal_price) {
    return '
    <button class="add-to-cart-btn w-full mt-2 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2"
            data-meal-id="' . $meal_id . '"
            data-meal-name="' . htmlspecialchars($meal_name) . '"
            data-meal-price="' . $meal_price . '">
        <i class="fas fa-cart-plus"></i>
        Add to Order
    </button>';
}

function getOrderSidebarHTML() {
    return '
    <div id="order-sidebar" class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300" style="background-color: var(--card-bg);">
        <div class="p-4 border-b" style="border-color: var(--border-color);">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Your Order</h2>
                <button onclick="toggleOrderSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="table-info" class="text-sm text-orange-custom mt-1"></div>
        </div>
        <div id="order-items" class="flex-1 overflow-y-auto p-4" style="max-height: calc(100vh - 200px);">
            <p class="text-gray-500 text-center py-8">Your cart is empty</p>
        </div>
        <div class="border-t p-4" style="border-color: var(--border-color);">
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Subtotal:</span>
                <span id="order-subtotal" class="font-bold text-orange-custom">$0.00</span>
            </div>
            <div class="mb-3">
                <input type="text" id="customer-name" placeholder="Your name (optional)" 
                       class="w-full px-3 py-2 border rounded-lg" style="background-color: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
            </div>
            <button onclick="checkout()" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
                Place Order
            </button>
        </div>
    </div>
    <div id="order-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleOrderSidebar()"></div>
    <button id="order-cart-btn" class="fixed bottom-4 right-4 bg-orange-custom text-white p-4 rounded-full shadow-lg z-40 hover:bg-orange-600 transition">
        <i class="fas fa-shopping-cart text-xl"></i>
        <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
    </button>';
}

function getOrderJavaScript() {
    return '
    <script>
        let cart = [];
        let tableNumber = ' . json_encode(getTableNumber()) . ';
        
        function toggleOrderSidebar() {
            document.getElementById("order-sidebar").classList.toggle("translate-x-full");
            document.getElementById("order-overlay").classList.toggle("hidden");
        }
        
        function addToCart(mealId, mealName, mealPrice) {
            const existingItem = cart.find(item => item.id === mealId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: mealId,
                    name: mealName,
                    price: parseFloat(mealPrice),
                    quantity: 1
                });
            }
            updateCartDisplay();
            saveCartToServer();
        }
        
        function updateCartDisplay() {
            const orderItems = document.getElementById("order-items");
            const cartCount = document.getElementById("cart-count");
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            if (cart.length === 0) {
                orderItems.innerHTML = \'<p class="text-gray-500 text-center py-8">Your cart is empty</p>\';
                cartCount.textContent = "0";
                document.getElementById("order-subtotal").textContent = "$0.00";
                return;
            }
            
            cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById("order-subtotal").textContent = "$" + subtotal.toFixed(2);
            
            orderItems.innerHTML = cart.map(item => `
                <div class="flex justify-between items-center mb-3 p-2 border rounded" style="border-color: var(--border-color);">
                    <div>
                        <p class="font-semibold">${item.name}</p>
                        <p class="text-sm text-gray-500">$${item.price.toFixed(2)} x ${item.quantity}</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})" class="text-red-500">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})" class="text-green-500">+</button>
                        <button onclick="removeFromCart(${item.id})" class="text-red-500 ml-2">×</button>
                    </div>
                </div>
            `).join("");
        }
        
        function updateQuantity(mealId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromCart(mealId);
                return;
            }
            const item = cart.find(i => i.id === mealId);
            if (item) {
                item.quantity = newQuantity;
                updateCartDisplay();
                saveCartToServer();
            }
        }
        
        function removeFromCart(mealId) {
            cart = cart.filter(item => item.id !== mealId);
            updateCartDisplay();
            saveCartToServer();
        }
        
        function saveCartToServer() {
            fetch("/save-cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    cart: cart,
                    table: tableNumber
                })
            }).catch(error => console.error("Error saving cart:", error));
        }
        
        function checkout() {
            if (cart.length === 0) {
                alert("Your cart is empty!");
                return;
            }
            
            const customerName = document.getElementById("customer-name").value;
            
            fetch("/place-order.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    cart: cart,
                    table: tableNumber,
                    customer_name: customerName,
                    subtotal: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Order placed successfully! Your food will be brought to Table " + tableNumber.replace("TABLE_", "") + " shortly.");
                    cart = [];
                    updateCartDisplay();
                    toggleOrderSidebar();
                } else {
                    alert("Error placing order: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error placing order. Please try again.");
            });
        }
        
        // Load cart from server on page load
        fetch("/get-cart.php?table=" + encodeURIComponent(tableNumber))
            .then(response => response.json())
            .then(data => {
                if (data.cart && data.cart.length > 0) {
                    cart = data.cart;
                    updateCartDisplay();
                }
            })
            .catch(error => console.error("Error loading cart:", error));
        
        // Display table info
        if (tableNumber) {
            const tableDisplay = tableNumber.replace("TABLE_", "");
            document.getElementById("table-info").innerHTML = `<i class="fas fa-table"></i> Table ${tableDisplay}`;
        }
        
        // Add event listeners after page load
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".add-to-cart-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    const mealId = parseInt(this.dataset.mealId);
                    const mealName = this.dataset.mealName;
                    const mealPrice = parseFloat(this.dataset.mealPrice);
                    addToCart(mealId, mealName, mealPrice);
                    toggleOrderSidebar();
                });
            });
        });
    </script>';
}
?>
