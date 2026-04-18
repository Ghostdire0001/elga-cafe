<?php
$page_title = 'System Settings';
require_once '../includes/config.php';
require_once '../includes/translations.php';
require_once '../includes/theme.php';
require_once '../includes/language.php';

$current_lang = getCurrentTheme();

// Check if user is super admin
$is_super_admin = ($_SESSION['user_role'] === 'admin');

if (!$is_super_admin) {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_feature = isset($_POST['order_feature_enabled']) ? '1' : '0';
    $order_minimum = floatval($_POST['order_minimum_amount']);
    $order_prep_time = intval($_POST['order_preparation_time']);
    $order_max_items = intval($_POST['order_max_items_per_order']);
    
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'order_feature_enabled'")->execute([$order_feature]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'order_minimum_amount'")->execute([$order_minimum]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'order_preparation_time'")->execute([$order_prep_time]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'order_max_items_per_order'")->execute([$order_max_items]);
    
    $message = "Settings updated successfully!";
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">System Settings</h1>
</div>

<?php if(isset($message)): ?>
    <div class="alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group full-width">
                <div class="bg-gray-50 p-4 rounded-lg mb-4" style="background-color: var(--bg-secondary);">
                    <h2 class="text-lg font-bold mb-3">🍽️ Ordering System</h2>
                    
                    <label class="checkbox-label mb-3 flex items-center">
                        <input type="checkbox" name="order_feature_enabled" value="1" <?php echo $settings['order_feature_enabled'] == '1' ? 'checked' : ''; ?> class="mr-3 w-5 h-5">
                        <span class="font-semibold">Enable Online Ordering System</span>
                    </label>
                    <p class="text-sm text-gray-500 ml-8">When enabled, customers will see "Add to Order" buttons on the menu.</p>
                </div>
            </div>
            
            <div id="order-settings" style="<?php echo $settings['order_feature_enabled'] == '1' ? '' : 'display: none;'; ?>">
                <div class="form-group">
                    <label class="form-label">Minimum Order Amount ($)</label>
                    <input type="number" step="0.01" name="order_minimum_amount" 
                           value="<?php echo $settings['order_minimum_amount']; ?>" 
                           class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Minimum total before customer can place order</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Default Preparation Time (minutes)</label>
                    <input type="number" name="order_preparation_time" 
                           value="<?php echo $settings['order_preparation_time']; ?>" 
                           class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Estimated time for order completion</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Maximum Items Per Order</label>
                    <input type="number" name="order_max_items_per_order" 
                           value="<?php echo $settings['order_max_items_per_order']; ?>" 
                           class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Limit items per single order</p>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<script>
    const orderToggle = document.querySelector('input[name="order_feature_enabled"]');
    const orderSettings = document.getElementById('order-settings');
    
    orderToggle.addEventListener('change', function() {
        orderSettings.style.display = this.checked ? 'block' : 'none';
    });
</script>

<?php require_once 'includes/footer.php'; ?>
