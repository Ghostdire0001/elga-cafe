<?php
$page_title = 'Manage Discounts';
require_once '../includes/config.php';
require_once 'includes/header.php';

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

// Display messages
if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Manage Discounts</h1>
    <a href="?action=add" class="btn-success">
        <i class="fas fa-plus"></i> Add Discount
    </a>
</div>

<?php if($action == 'add' || ($action == 'edit' && $discount)): ?>
    <div class="form-container">
        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Discount' : 'Edit Discount'; ?></h2>
        <form method="POST" action="" id="discountForm">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Discount Code *</label>
                    <input type="text" name="code" required value="<?php echo $discount ? $discount['code'] : ''; ?>" placeholder="WELCOME10" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Discount Name *</label>
                    <input type="text" name="name" required value="<?php echo $discount ? $discount['name'] : ''; ?>" placeholder="Welcome Discount" class="form-input">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea"><?php echo $discount ? htmlspecialchars($discount['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Discount Type *</label>
                    <select name="discount_type" required class="form-select">
                        <option value="percentage" <?php echo ($discount && $discount['discount_type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        <option value="fixed_amount" <?php echo ($discount && $discount['discount_type'] == 'fixed_amount') ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Discount Value *</label>
                    <input type="number" step="0.01" name="discount_value" required value="<?php echo $discount ? $discount['discount_value'] : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="datetime-local" name="start_date" required value="<?php echo $discount ? date('Y-m-d\TH:i', strtotime($discount['start_date'])) : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date *</label>
                    <input type="datetime-local" name="end_date" required value="<?php echo $discount ? date('Y-m-d\TH:i', strtotime($discount['end_date'])) : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Minimum Order Amount</label>
                    <input type="number" step="0.01" name="minimum_order_amount" value="<?php echo $discount ? $discount['minimum_order_amount'] : '0'; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Maximum Discount Amount</label>
                    <input type="number" step="0.01" name="maximum_discount_amount" value="<?php echo $discount ? $discount['maximum_discount_amount'] : ''; ?>" placeholder="Optional" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" name="usage_limit" value="<?php echo $discount ? $discount['usage_limit'] : ''; ?>" placeholder="Unlimited" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Applicable To *</label>
                    <select name="applicable_to" id="applicable_to" required class="form-select">
                        <option value="all" <?php echo ($discount && $discount['applicable_to'] == 'all') ? 'selected' : ''; ?>>All Meals</option>
                        <option value="specific_meals" <?php echo ($discount && $discount['applicable_to'] == 'specific_meals') ? 'selected' : ''; ?>>Specific Meals</option>
                        <option value="specific_categories" <?php echo ($discount && $discount['applicable_to'] == 'specific_categories') ? 'selected' : ''; ?>>Specific Categories</option>
                    </select>
                </div>
                
                <div id="specific_meals_div" style="display: none;" class="form-group full-width">
                    <label class="form-label">Select Meals</label>
                    <div class="checkbox-group">
                        <?php foreach($meals as $meal): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="specific_meals[]" value="<?php echo $meal['id']; ?>"
                                       <?php echo in_array($meal['id'], $specific_meals) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($meal['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div id="specific_categories_div" style="display: none;" class="form-group full-width">
                    <label class="form-label">Select Categories</label>
                    <div class="checkbox-group">
                        <?php foreach($categories as $cat): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="specific_categories[]" value="<?php echo $cat['id']; ?>"
                                       <?php echo in_array($cat['id'], $specific_categories) ? 'checked' : ''; ?>>
                                <?php echo ucfirst($cat['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?php echo ($discount && $discount['is_active']) || !$discount ? 'checked' : ''; ?>>
                        Active
                    </label>
                </div>
            </div>
            
            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Save Discount' : 'Update Discount'; ?>
                </button>
                <a href="discounts.php" class="btn-secondary">Cancel</a>
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
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Value</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($discounts as $item): ?>
                    <tr>
                        <td><strong class="text-orange-custom"><?php echo $item['code']; ?></strong></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>
                            <?php echo $item['discount_type'] == 'percentage' ? $item['discount_value'] . '%' : '$' . number_format($item['discount_value'], 2); ?>
                        </td>
                        <td>
                            <?php echo date('M d', strtotime($item['start_date'])); ?> - <?php echo date('M d, Y', strtotime($item['end_date'])); ?>
                        </td>
                        <td>
                            <span class="<?php echo $item['status'] == 'Active' ? 'badge-success' : ($item['status'] == 'Upcoming' ? 'badge-warning' : 'badge-danger'); ?>">
                                <?php echo $item['status']; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?')" class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
