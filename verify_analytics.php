<?php
/**
 * Visitor Analytics System Verification Script
 * This script verifies that the visitor analytics system is properly installed and configured
 */

require_once 'includes/config.php';

echo "=== VISITOR ANALYTICS SYSTEM VERIFICATION ===\n\n";

$tests_passed = 0;
$tests_total = 0;

function test_result($test_name, $passed, $message = '') {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($passed) {
        $tests_passed++;
        echo "✅ $test_name: PASS\n";
    } else {
        echo "❌ $test_name: FAIL - $message\n";
    }
}

// Test 1: Database connection
try {
    $stmt = $pdo->query("SELECT 1");
    test_result("Database Connection", true);
} catch (Exception $e) {
    test_result("Database Connection", false, $e->getMessage());
}

// Test 2: Visitor tracking tables exist
try {
    $required_tables = ['visitor_sessions', 'visitor_analytics', 'visitor_locations', 'page_views'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    test_result("Visitor Tables", empty($missing_tables), 
                empty($missing_tables) ? '' : 'Missing: ' . implode(', ', $missing_tables));
} catch (Exception $e) {
    test_result("Visitor Tables", false, $e->getMessage());
}

// Test 3: VisitorTracker class
try {
    require_once 'includes/visitor_tracker.php';
    $tracker = new VisitorTracker($pdo);
    test_result("VisitorTracker Class", true);
} catch (Exception $e) {
    test_result("VisitorTracker Class", false, $e->getMessage());
}

// Test 4: Analytics files exist
$analytics_files = [
    'admin/visitor-analytics.php' => 'Analytics Dashboard',
    'includes/visitor_analytics_widget.php' => 'Dashboard Widget',
    'visitor_tracking_schema.sql' => 'Database Schema',
    'VISITOR_ANALYTICS_README.md' => 'Documentation'
];

foreach ($analytics_files as $file => $description) {
    test_result($description, file_exists($file), "File not found: $file");
}

// Test 5: Test visitor tracking functionality
try {
    $tracker = new VisitorTracker($pdo);
    
    // Get current stats using static method
    $stats = VisitorTracker::getAnalytics($pdo, 30); // Get last 30 days
    test_result("Analytics Data Retrieval", is_array($stats));
    
    // Test basic tracking functionality
    $tracker->trackVisit("Test Page");
    test_result("Visit Tracking", true, "Successfully tracked test visit");
    
} catch (Exception $e) {
    test_result("Visitor Tracking Functionality", false, $e->getMessage());
}

// Test 6: Check for sample data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitor_sessions");
    $session_count = $stmt->fetch()['count'];
    test_result("Sample Data Present", $session_count > 0, 
                $session_count == 0 ? 'No visitor sessions found' : "$session_count sessions found");
} catch (Exception $e) {
    test_result("Sample Data Present", false, $e->getMessage());
}

// Test 7: ToyyibPay Integration (bonus check)
try {
    require_once 'includes/toyyibpay.php';
    $toyyibpay = new ToyyibPay($pdo);
    test_result("ToyyibPay Integration", true);
} catch (Exception $e) {
    test_result("ToyyibPay Integration", false, $e->getMessage());
}

echo "\n=== VERIFICATION SUMMARY ===\n";
echo "Tests Passed: $tests_passed/$tests_total\n";

if ($tests_passed == $tests_total) {
    echo "🎉 ALL TESTS PASSED! Visitor Analytics System is fully operational.\n\n";
    
    echo "=== QUICK ACCESS LINKS ===\n";
    echo "🔗 Admin Dashboard: https://shazwan-danial.my/wedding-management-system/admin/dashboard.php\n";
    echo "📊 Visitor Analytics: https://shazwan-danial.my/wedding-management-system/admin/visitor-analytics.php\n";
    echo "🧪 System Test: https://shazwan-danial.my/wedding-management-system/system_test.php\n";
    echo "💳 Payment Gateway: https://shazwan-danial.my/wedding-management-system/admin/toyyibpay-management.php\n";
    
    echo "\n=== FEATURES AVAILABLE ===\n";
    echo "✨ Real-time visitor tracking with device detection\n";
    echo "📈 Interactive analytics dashboard with charts\n";
    echo "📱 Mobile/Desktop/Tablet usage analytics\n";
    echo "🌍 IP-based geographic tracking\n";
    echo "💼 ToyyibPay payment gateway integration\n";
    echo "🎯 Dashboard widgets for quick insights\n";
    
} else {
    echo "⚠️  Some tests failed. Please check the issues above.\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Visit the admin dashboard to see the visitor analytics widget\n";
echo "2. Access the detailed analytics page for comprehensive insights\n";
echo "3. Monitor real-time visitor data and patterns\n";
echo "4. Use ToyyibPay integration for payment processing\n";

echo "\nGenerated: " . date('Y-m-d H:i:s') . "\n";
?>