<?php
require_once 'includes/config.php';

echo "<h2>System Test Results</h2>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $user_count = $stmt->fetch()['total'];
    echo "✅ Database connection: OK (Users: $user_count)<br>";
} catch (Exception $e) {
    echo "❌ Database connection: Failed - " . $e->getMessage() . "<br>";
}

// Test ToyyibPay integration
try {
    require_once 'includes/toyyibpay.php';
    $toyyibpay = new ToyyibPay($pdo);
    echo "✅ ToyyibPay class: OK<br>";
} catch (Exception $e) {
    echo "❌ ToyyibPay class: Failed - " . $e->getMessage() . "<br>";
}

// Test visitor tracking
try {
    require_once 'includes/visitor_tracker.php';
    $visitor_tracker = new VisitorTracker($pdo);
    
    // Check if visitor tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitor_%'");
    $visitor_tables = $stmt->fetchAll();
    
    if (count($visitor_tables) >= 4) {
        echo "✅ Visitor tracking: OK (Tables: " . count($visitor_tables) . ")<br>";
    } else {
        echo "⚠️ Visitor tracking: Missing tables (Found: " . count($visitor_tables) . "/4)<br>";
        echo "Run visitor_tracking_schema.sql to create tables<br>";
    }
} catch (Exception $e) {
    echo "❌ Visitor tracking: Failed - " . $e->getMessage() . "<br>";
}

// Test admin access
if (file_exists('admin/dashboard.php')) {
    echo "✅ Admin dashboard: Available<br>";
} else {
    echo "❌ Admin dashboard: Missing<br>";
}

// Test customer portal
if (file_exists('customer/dashboard.php')) {
    echo "✅ Customer portal: Available<br>";
} else {
    echo "❌ Customer portal: Missing<br>";
}

// Test vendor portal
if (file_exists('vendor/dashboard.php')) {
    echo "✅ Vendor portal: Available<br>";
} else {
    echo "❌ Vendor portal: Missing<br>";
}

echo "<hr>";
echo "<h3>Quick Links</h3>";
echo "<a href='admin/dashboard.php'>Admin Dashboard</a> | ";
echo "<a href='customer/dashboard.php'>Customer Portal</a> | ";
echo "<a href='vendor/dashboard.php'>Vendor Portal</a> | ";
echo "<a href='admin/visitor-analytics.php'>Visitor Analytics</a><br>";

echo "<hr>";
echo "<h3>System Information</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f4f4f4;
}
h2, h3 {
    color: #333;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>