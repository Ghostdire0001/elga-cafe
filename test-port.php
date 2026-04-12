<?php
$host = 'fhunzl.h.filess.io';
$port = 3306;

echo "<h1>Testing Database Connection</h1>";

// Test if host is reachable
$connection = @fsockopen($host, $port, $errno, $errstr, 5);

if ($connection) {
    echo "<p style='color:green'>✓ Host $host on port $port is reachable!</p>";
    fclose($connection);
} else {
    echo "<p style='color:red'>✗ Cannot reach $host on port $port</p>";
    echo "<p>Error: $errstr ($errno)</p>";
}

// Test MySQL connection with credentials
try {
    $test = new PDO("mysql:host=$host;dbname=meal_menu_db_sometimego;port=$port", 
                    'meal_menu_db_sometimego', 
                    '238446391c2971f7d2668dd6be72bf408400ce26');
    echo "<p style='color:green'>✓ MySQL connection successful with credentials!</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>✗ MySQL connection failed: " . $e->getMessage() . "</p>";
}
?>
