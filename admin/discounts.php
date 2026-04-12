<?php
require_once '../includes/auth.php';

$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $code = strtoupper(trim($_POST['code']));
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $minimum_order_amount = floatval($_POST['minimum_order_amount']);
    $maximum_discount_amount = !empty($_POST['maximum_discount_amount']) ? floatval($_POST['maximum_discount_amount']) : null;
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $applicable_to = $_POST['applicable_to'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO discounts (code, name, description, discount_type, discount_value, start_date, end_date, minimum_order_amount, maximum_discount_amount, usage_limit, applicable_to, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $start_date, $end_date, $minimum_order_amount, $maximum_discount_amount, $usage_limit, $applicable_to, $is_active]);
        $discount_id = $pdo->lastInsertId();
        $message = "Discount added successfully!";
    } else {
        $discount_id = intval($_POST['discount_id']);
        $stmt = $pdo->prepare("UPDATE discounts SET code=?, name=?, description=?, discount_type=?, discount_value=?, start_date=?, end_date=?, minimum_order_amount=?, maximum_discount_amount=?, usage_limit=?, applicable_to=?, is_active=? WHERE id=?");
        $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $start_date, $end_date, $minimum_order_amount, $maximum_discount_amount, $usage_limit, $applicable_to, $is_active, $discount_id]);
        $message = "Discount updated successfully!";
        
        // Clear specific items
        $pdo->prepare("DELETE FROM discount_meals WHERE discount_id = ?")->execute([$discount_id]);
        $pdo->prepare("DELETE FROM discount_categories WHERE discount_id = ?")->execute([$discount_id]);
    }
    
    // Handle specific items for applicable_to
    if($applicable_to == 'specific_meals' && isset($_POST['specific_meals'])) {
        foreach($_POST['specific_meals'] as $meal_id) {
            $stmt = $pdo->prepare("INSERT INTO discount_meals (discount_id, meal_id) VALUES (?, ?)");
            $stmt->execute([$discount_id, $meal_id]);
        }
    } elseif($applicable_to == 'specific_categories' && isset($_POST['specific_categories'])) {
        foreach($_POST['specific_categories'] as $category_id) {
            $stmt = $pdo->prepare("INSERT INTO discount_categories (discount_id, category_id) VALUES (?, ?)");
            $stmt->execute([$discount_id, $category_id]);
        }
    }
    
    header("Location: discounts.php?message=" . urlencode($message));
    exit();
}

// Handle Delete
if($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $pdo->prepare("DELETE FROM discount_meals WHERE discount_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM discount_categories WHERE discount_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM discounts WHERE id = ?")->execute([$id]);
    header("Location: discounts.php?message=Discount deleted successfully");
    exit();
}

// Get discount for edit
$discount = null;
$specific_meals = [];
$specific_categories = [];
if($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->execute([$id]);
    $discount = $stmt->fetch();
    
    if($discount) {
        $specific_meals = $pdo->query("SELECT meal_id FROM discount_meals WHERE discount_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $specific_categories = $pdo->query("SELECT category_id FROM discount_categories WHERE discount_id = $id")->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Get data for dropdowns
$meals = $pdo->query("SELECT id, name FROM meals ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY display_order")->fetchAll();
$discounts = $pdo->query("SELECT d.*, 
                          CASE 
                              WHEN NOW() BETWEEN d.start_date AND d.end_date THEN 'Active'
                              WHEN NOW() < d.start_date THEN 'Upcoming'
                              ELSE 'Expired'
                          END as status
                          FROM discounts d ORDER BY d.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discounts - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
                <p class="text-sm text-gray-400">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
                <a href="meals.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-utensils mr-2"></i> Meals</a>
                <a href="categories.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tags mr-2"></i> Categories</a>
                <a href="dietary-labels.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-leaf mr-2"></i> Dietary Labels</a>
                <a href="discounts.php" class="block py-2 px-4 bg-orange-custom hover:bg-orange-600"><i class="fas fa-tag mr-2"></i> Discounts</a>
                <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 mt-8"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-8">
                <?php if(isset($_GET['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Manage Discounts</h1>
                    <a href="?action=add" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"><i class="fas fa-plus mr-2"></i> Add Discount</a>
                </div>
                
                <?php if($action == 'add' || ($action == 'edit' && $discount)): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Discount' : 'Edit Discount'; ?></h2>
                        <form method="POST" action="">
                            <?php if($action == 'edit'): ?>
                                <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Discount Code *</label>
                                    <input type="text" name="code" required value="<?php echo $discount ? $discount['code'] : ''; ?>" 
                                           placeholder="WELCOME10"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Discount Name *</label>
                                    <input type="text" name="name" required value="<?php echo $discount ? $discount['name'] : ''; ?>" 
                                           placeholder="Welcome Discount"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 font-bold mb-2">Description</label>
                                    <textarea name="description" rows="2" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom"><?php echo $discount ? htmlspecialchars($discount['description']) : ''; ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Discount Type *</label>
                                    <select name="discount_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                        <option value="percentage" <?php echo ($discount && $discount['discount_type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                        <option value="fixed_amount" <?php echo ($discount && $discount['discount_type'] == 'fixed_amount') ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Discount Value *</label>
                                    <input type="number" step="0.01" name="discount_value" required value="<?php echo $discount ? $discount['discount_value'] : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Start Date *</label>
                                    <input type="datetime-local" name="start_date" required value="<?php echo $discount ? date('Y-m-d\TH:i', strtotime($discount['start_date'])) : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">End Date *</label>
                                    <input type="datetime-local" name="end_date" required value="<?php echo $discount ? date('Y-m-d\TH:i', strtotime($discount['end_date'])) : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Minimum Order Amount</label>
                                    <input type="number" step="0.01" name="minimum_order_amount" value="<?php echo $discount ? $discount['minimum_order_amount'] : '0'; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Maximum Discount Amount</label>
                                    <input type="number" step="0.01" name="maximum_discount_amount" value="<?php echo $discount ? $discount['maximum_discount_amount'] : ''; ?>" 
                                           placeholder="Optional"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Usage Limit</label>
                                    <input type="number" name="usage_limit" value="<?php echo $discount ? $discount['usage_limit'] : ''; ?>" 
                                           placeholder="Unlimited"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Applicable To *</label>
                                    <select name="applicable_to" id="applicable_to" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                        <option value="all" <?php echo ($discount && $discount['applicable_to'] == 'all') ? 'selected' : ''; ?>>All Meals</option>
                                        <option value="specific_meals" <?php echo ($discount && $discount['applicable_to'] == 'specific_meals') ? 'selected' : ''; ?>>Specific Meals</option>
                                        <option value="specific_categories" <?php echo ($discount && $discount['applicable_to'] == 'specific_categories') ? 'selected' : ''; ?>>Specific Categories</option>
                                    </select>
                                </div>
                                
                                <div id="specific_meals_div" style="display: none;" class="md:col-span-2">
                                    <label class="block text-gray-700 font-bold mb-2">Select Meals</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        <?php foreach($meals as $meal): ?>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="specific_meals[]" value="<?php echo $meal['id']; ?>"
                                                       <?php echo in_array($meal['id'], $specific_meals) ? 'checked' : ''; ?>
                                                       class="mr-2">
                                                <?php echo htmlspecialchars($meal['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div id="specific_categories_div" style="display: none;" class="md:col-span-2">
                                    <label class="block text-gray-700 font-bold mb-2">Select Categories</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <?php foreach($categories as $cat): ?>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="specific_categories[]" value="<?php echo $cat['id']; ?>"
                                                       <?php echo in_array($cat['id'], $specific_categories) ? 'checked' : ''; ?>
                                                       class="mr-2">
                                                <?php echo ucfirst($cat['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_active" <?php echo ($discount && $discount['is_active']) || !$discount ? 'checked' : ''; ?> class="mr-2">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-orange-custom text-white px-6 py-2 rounded hover:bg-orange-600">
                                    <i class="fas fa-save mr-2"></i> <?php echo $action == 'add' ? 'Save Discount' : 'Update Discount'; ?>
                                </button>
                                <a href="discounts.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Cancel</a>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                        const applicableTo = document.getElementById('applicable_to');
                        const specificMealsDiv = document.getElementById('specific_meals_div');
                        const specificCategoriesDiv = document.getElementById('specific_categories_div');
                        
                        function updateSpecificDivs() {
                            const value = applicableTo.value;
                            specificMealsDiv.style.display = value === 'specific_meals' ? 'block' : 'none';
                            specificCategoriesDiv.style.display = value === 'specific_categories' ? 'block' : 'none';
                        }
                        
                        applicableTo.addEventListener('change', updateSpecificDivs);
                        updateSpecificDivs();
                    </script>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($discounts as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 font-bold text-orange-custom"><?php echo $item['code']; ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="px-6 py-4">
                                            <?php echo $item['discount_type'] == 'percentage' ? $item['discount_value'] . '%' : '$' . number_format($item['discount_value'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php echo date('M d', strtotime($item['start_date'])); ?> - <?php echo date('M d, Y', strtotime($item['end_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            if($item['status'] == 'Active') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusText = 'Active';
                                            } elseif($item['status'] == 'Upcoming') {
                                                $statusClass = 'bg-blue-100 text-blue-800';
                                                $statusText = 'Upcoming';
                                            } else {
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                $statusText = 'Expired';
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>