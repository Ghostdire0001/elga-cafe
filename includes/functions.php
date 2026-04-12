<?php
// Make sure config is loaded
if(!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

// Get all categories
function getCategories($pdo, $activeOnly = true) {
    try {
        $sql = "SELECT * FROM categories";
        if($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting categories: " . $e->getMessage());
        return [];
    }
}

// Get all dietary labels
function getDietaryLabels($pdo) {
    try {
        $sql = "SELECT * FROM dietary_labels ORDER BY id";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting dietary labels: " . $e->getMessage());
        return [];
    }
}

// Get meals with filters
function getMeals($pdo, $category_id = null, $dietary_ids = [], $search = '', $featured = false, $popular = false) {
    try {
        $sql = "SELECT m.*, c.name as category_name 
                FROM meals m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.availability = 1";
        $params = [];
        
        if($category_id && $category_id > 0) {
            $sql .= " AND m.category_id = ?";
            $params[] = $category_id;
        }
        
        if($featured) {
            $sql .= " AND m.is_featured = 1";
        }
        
        if($popular) {
            $sql .= " AND m.is_popular = 1";
        }
        
        if(!empty($search)) {
            $sql .= " AND (m.name LIKE ? OR m.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if(!empty($dietary_ids)) {
            $placeholders = implode(',', array_fill(0, count($dietary_ids), '?'));
            $sql .= " AND m.id IN (SELECT meal_id FROM meal_dietary_labels WHERE dietary_label_id IN ($placeholders))";
            $params = array_merge($params, $dietary_ids);
        }
        
        $sql .= " ORDER BY c.display_order, m.is_featured DESC, m.is_popular DESC, m.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting meals: " . $e->getMessage());
        return [];
    }
}

// Get active discounts for a meal
function getMealDiscount($pdo, $meal_id, $meal_price, $category_id) {
    try {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM discounts 
                WHERE is_active = 1 
                AND start_date <= ? 
                AND end_date >= ?
                AND (usage_limit IS NULL OR used_count < usage_limit)
                AND (applicable_to = 'all' 
                     OR (applicable_to = 'specific_meals' AND id IN (SELECT discount_id FROM discount_meals WHERE meal_id = ?))
                     OR (applicable_to = 'specific_categories' AND id IN (SELECT discount_id FROM discount_categories WHERE category_id = ?)))
                ORDER BY discount_value DESC LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$now, $now, $meal_id, $category_id]);
        $discount = $stmt->fetch();
        
        if($discount) {
            if($discount['discount_type'] == 'percentage') {
                $discounted_price = $meal_price * (1 - $discount['discount_value'] / 100);
                if($discount['maximum_discount_amount'] && $discount['maximum_discount_amount'] > 0) {
                    $saved = $meal_price - $discounted_price;
                    if($saved > $discount['maximum_discount_amount']) {
                        $discounted_price = $meal_price - $discount['maximum_discount_amount'];
                    }
                }
            } else {
                $discounted_price = max(0, $meal_price - $discount['discount_value']);
            }
            $discount['discounted_price'] = round($discounted_price, 2);
            return $discount;
        }
        return null;
    } catch(PDOException $e) {
        error_log("Error getting discount: " . $e->getMessage());
        return null;
    }
}

// Get meal dietary labels
function getMealDietaryLabels($pdo, $meal_id) {
    try {
        $sql = "SELECT dl.* FROM dietary_labels dl 
                INNER JOIN meal_dietary_labels mdl ON dl.id = mdl.dietary_label_id 
                WHERE mdl.meal_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$meal_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting meal dietary labels: " . $e->getMessage());
        return [];
    }
}
?>