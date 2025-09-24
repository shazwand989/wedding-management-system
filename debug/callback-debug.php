<?php
/**
 * ToyyibPay Callback Test & Debug Tool
 * Tests if callbacks are working and logs all activity
 */

require_once '../includes/config.php';

echo "<h2>🔍 ToyyibPay Callback Debug Tool</h2>";

$action = $_GET['action'] ?? 'view';

if ($action === 'test_callback') {
    echo "<h3>🧪 Testing Callback Functionality</h3>";
    
    // Create a test callback payload
    $test_data = [
        'billcode' => 'TEST_CALLBACK_' . time(),
        'order_id' => '1000',
        'status' => '1',
        'amount' => '5000', // RM 50.00 in cents
        'transaction_id' => 'TXN_' . time(),
        'payment_date' => date('Y-m-d H:i:s')
    ];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Test Callback Data:</h4>";
    echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    // Test the callback URL
    $callback_url = 'https://shazwan-danial.my/wedding-management-system/includes/toyyibpay-callback.php';
    
    echo "<h4>📡 Sending Test Callback to: <code>$callback_url</code></h4>";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $callback_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: " . ($http_code == 200 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>📊 Callback Test Results:</h4>";
    echo "<p><strong>HTTP Status:</strong> " . $http_code . "</p>";
    echo "<p><strong>Response:</strong> " . ($response ?: 'No response') . "</p>";
    if ($curl_error) {
        echo "<p><strong>cURL Error:</strong> " . $curl_error . "</p>";
    }
    echo "</div>";
    
} elseif ($action === 'view_logs') {
    echo "<h3>📋 Recent Callback Logs</h3>";
    
    // Check if we can access error logs
    $log_locations = [
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        '/tmp/php_errors.log',
        ini_get('error_log')
    ];
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🔍 Looking for ToyyibPay callback logs in:</h4>";
    echo "<ul>";
    foreach ($log_locations as $log_file) {
        if ($log_file && file_exists($log_file) && is_readable($log_file)) {
            echo "<li>✅ $log_file (accessible)</li>";
            
            // Try to read recent ToyyibPay logs
            $recent_logs = shell_exec("tail -100 '$log_file' 2>/dev/null | grep -i 'toyyibpay' | tail -10");
            if ($recent_logs) {
                echo "<div style='background: white; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;'>";
                echo "<strong>Recent ToyyibPay logs from $log_file:</strong><br>";
                echo "<pre style='white-space: pre-wrap; margin: 5px 0;'>" . htmlspecialchars($recent_logs) . "</pre>";
                echo "</div>";
            }
        } else {
            echo "<li>❌ $log_file (not accessible)</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
    
} elseif ($action === 'check_callback_url') {
    echo "<h3>🌐 Callback URL Accessibility Check</h3>";
    
    $callback_url = 'https://shazwan-danial.my/wedding-management-system/includes/toyyibpay-callback.php';
    
    echo "<p>Testing if callback URL is accessible: <code>$callback_url</code></p>";
    
    // Test GET request (should return Method not allowed)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $callback_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🔗 URL Test Results:</h4>";
    echo "<p><strong>HTTP Status:</strong> " . $http_code . "</p>";
    echo "<p><strong>Expected:</strong> 405 (Method Not Allowed) - this is correct for GET requests</p>";
    echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
    if ($curl_error) {
        echo "<p><strong>Error:</strong> " . $curl_error . "</p>";
    }
    
    if ($http_code == 405) {
        echo "<div style='color: green;'><strong>✅ SUCCESS:</strong> Callback URL is accessible and correctly rejecting GET requests</div>";
    } else {
        echo "<div style='color: red;'><strong>❌ ISSUE:</strong> Callback URL may not be accessible or configured properly</div>";
    }
    echo "</div>";
    
} else {
    // Main menu
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
    echo "<p>This tool helps debug ToyyibPay callback issues and test callback functionality.</p>";
    echo "<p><strong>Current Callback URL:</strong> <code>https://shazwan-danial.my/wedding-management-system/includes/toyyibpay-callback.php</code></p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<h3>🔧 Debug Options:</h3>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    
    echo "<a href='?action=check_callback_url' style='background: #007bff; color: white; padding: 15px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>";
    echo "🌐 Check Callback URL";
    echo "</a>";
    
    echo "<a href='?action=test_callback' style='background: #28a745; color: white; padding: 15px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>";
    echo "🧪 Test Callback";
    echo "</a>";
    
    echo "<a href='?action=view_logs' style='background: #17a2b8; color: white; padding: 15px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>";
    echo "📋 View Logs";
    echo "</a>";
    
    echo "</div>";
    echo "</div>";
    
    // Show recent callback activity from database
    echo "<h3>📊 Database Activity</h3>";
    
    try {
        // Check toyyibpay_transactions table
        $stmt = $pdo->query("
            SELECT COUNT(*) as count,
                   MAX(created_at) as last_created,
                   MAX(updated_at) as last_updated
            FROM toyyibpay_transactions
        ");
        $transaction_stats = $stmt->fetch();
        
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>💾 ToyyibPay Transactions Table:</h4>";
        echo "<p><strong>Total Records:</strong> " . $transaction_stats['count'] . "</p>";
        echo "<p><strong>Last Created:</strong> " . ($transaction_stats['last_created'] ?: 'None') . "</p>";
        echo "<p><strong>Last Updated:</strong> " . ($transaction_stats['last_updated'] ?: 'None') . "</p>";
        echo "</div>";
        
        // Check recent payments
        $stmt = $pdo->query("
            SELECT COUNT(*) as count,
                   MAX(payment_date) as last_payment
            FROM payments 
            WHERE payment_method IN ('online', 'toyyibpay')
            AND payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $payment_stats = $stmt->fetch();
        
        echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>💳 Recent Online Payments (Last 7 Days):</h4>";
        echo "<p><strong>Count:</strong> " . $payment_stats['count'] . "</p>";
        echo "<p><strong>Last Payment:</strong> " . ($payment_stats['last_payment'] ?: 'None') . "</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>❌ Database Error:</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 20px; 
        background: #f5f5f5; 
        line-height: 1.6;
    }
    h2, h3, h4 { color: #333; }
    code { 
        background: #f1f1f1; 
        padding: 2px 4px; 
        border-radius: 3px; 
        font-family: 'Courier New', monospace; 
    }
    pre { 
        background: #f8f9fa; 
        padding: 10px; 
        border-radius: 5px; 
        overflow-x: auto; 
    }
    a { text-decoration: none; }
    a:hover { opacity: 0.8; }
</style>