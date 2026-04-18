<?php
// Settings helper functions

function getSetting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

function isOrderEnabled($pdo) {
    return getSetting($pdo, 'order_feature_enabled', '0') == '1';
}

function getOrderMinimumAmount($pdo) {
    return floatval(getSetting($pdo, 'order_minimum_amount', '10.00'));
}

function getOrderPreparationTime($pdo) {
    return intval(getSetting($pdo, 'order_preparation_time', '30'));
}
?>
