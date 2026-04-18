<?php
// Order system functions - loaded only when order feature is enabled

function isOrderFeatureEnabled($pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'order_feature_enabled'");
    $stmt->execute();
    return $stmt->fetchColumn() == '1';
}

function getOrderSettings($pdo) {
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
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
    <div id="order-sidebar" class="fixed right-0 top-0 h-full w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300" style="background-color: var(--card-bg);">
        <div class="p-4 border-b" style="border-color: var(--border-color);">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Your Order</h2>
                <button onclick="toggleOrderSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="order-items" class="flex-1 overflow-y-auto p-4" style="max-height: calc(100vh - 200px);">
            <p class="text-gray-500 text-center py-8">Your cart is empty</p>
        </div>
        <div class="border-t p-4" style="border-color: var(--border-color);">
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Subtotal:</span>
                <span id="order-subtotal" class="font-bold text-orange-custom">$0.00</span>
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
            }
        }
        
        function removeFromCart(mealId) {
            cart = cart.filter(item => item.id !== mealId);
            updateCartDisplay();
        }
        
        function checkout() {
            if (cart.length === 0) {
                alert("Your cart is empty!");
                return;
            }
            
            // Here you would send the order to your backend
            console.log("Order placed:", cart);
            alert("Order placed! Thank you for your order.");
            cart = [];
            updateCartDisplay();
            toggleOrderSidebar();
        }
        
        // Add event listeners to all "Add to Order" buttons
        document.querySelectorAll(".add-to-cart-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const mealId = this.dataset.mealId;
                const mealName = this.dataset.mealName;
                const mealPrice = this.dataset.mealPrice;
                addToCart(mealId, mealName, mealPrice);
                toggleOrderSidebar();
            });
        });
    </script>';
}
?>
