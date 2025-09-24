<?php
/**
 * Simple ToyyibPay Test Page
 * Use this to quickly test your ToyyibPay integration
 */

require_once 'includes/config.php';
require_once 'includes/toyyibpay.php';

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied. Please login as admin first.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ToyyibPay Integration Test</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>ToyyibPay Integration Test</h2>
    
    <?php
    try {
        $toyyibpay = new ToyyibPay();
        echo '<div class="alert alert-success">✓ ToyyibPay class loaded successfully</div>';
        
        if ($_POST && isset($_POST['test_bill'])) {
            // Test bill creation
            $testBillParams = [
                'billName' => 'Test Payment - ' . date('Y-m-d H:i:s'),
                'billDescription' => 'Integration test payment',
                'billAmount' => 10.00, // RM 10.00 for testing
                'billExternalReferenceNo' => 'TEST_' . time(),
                'billTo' => 'Test Customer',
                'billEmail' => 'test@example.com',
                'billPhone' => '60123456789'
            ];
            
            echo '<h4>Testing Bill Creation</h4>';
            $result = $toyyibpay->createBill($testBillParams);
            
            if ($result['success']) {
                echo '<div class="alert alert-success">';
                echo '<h5>✓ Test bill created successfully!</h5>';
                echo '<p><strong>Bill Code:</strong> ' . $result['billCode'] . '</p>';
                echo '<p><strong>Payment URL:</strong> <a href="' . $result['paymentUrl'] . '" target="_blank" class="btn btn-primary btn-sm">Open Payment Page</a></p>';
                echo '<p class="small"><strong>Note:</strong> This is a test bill for RM 10.00. You can use it to test the payment flow in sandbox mode.</p>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">';
                echo '<h5>✗ Failed to create test bill</h5>';
                echo '<p><strong>Error:</strong> ' . ($result['error'] ?? 'Unknown error') . '</p>';
                if (isset($result['response'])) {
                    echo '<pre>' . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . '</pre>';
                }
                echo '</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">✗ Error: ' . $e->getMessage() . '</div>';
    }
    ?>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Configuration Check</h5>
        </div>
        <div class="card-body">
            <p>Make sure to configure the following URLs in your ToyyibPay dashboard:</p>
            <ul>
                <li><strong>Return URL:</strong> <code><?php echo SITE_URL; ?>customer/payment-return.php</code></li>
                <li><strong>Callback URL:</strong> <code><?php echo SITE_URL; ?>includes/toyyibpay-callback.php</code></li>
            </ul>
            
            <h6>Quick Actions:</h6>
            <form method="POST" class="mb-3">
                <button type="submit" name="test_bill" class="btn btn-primary">
                    Create Test Bill (RM 10.00)
                </button>
            </form>
            
            <div class="btn-group">
                <a href="admin/toyyibpay-management.php" class="btn btn-success">ToyyibPay Management</a>
                <a href="admin/payments.php" class="btn btn-info">View Payments</a>
                <a href="customer/dashboard.php" class="btn btn-secondary">Customer Dashboard</a>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6>Integration Status</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?php
                    $checks = [
                        'ToyyibPay class' => class_exists('ToyyibPay'),
                        'cURL extension' => extension_loaded('curl'),
                        'JSON extension' => extension_loaded('json'),
                        'Callback file' => file_exists('includes/toyyibpay-callback.php'),
                        'Payment page' => file_exists('customer/payment.php'),
                        'Return page' => file_exists('customer/payment-return.php'),
                    ];
                    
                    foreach ($checks as $check => $status) {
                        $class = $status ? 'text-success' : 'text-danger';
                        $icon = $status ? '✓' : '✗';
                        echo "<p class='$class'>$icon $check</p>";
                    }
                    ?>
                </div>
                <div class="col-md-6">
                    <h6>Database Tables</h6>
                    <?php
                    $tables = ['payments', 'bookings', 'users'];
                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            $exists = $stmt->rowCount() > 0;
                            $class = $exists ? 'text-success' : 'text-danger';
                            $icon = $exists ? '✓' : '✗';
                            echo "<p class='$class'>$icon Table: $table</p>";
                        } catch (Exception $e) {
                            echo "<p class='text-warning'>? Table: $table (check failed)</p>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <h6>Next Steps for Production:</h6>
        <ol>
            <li>Update ToyyibPay credentials in <code>includes/toyyibpay.php</code></li>
            <li>Change base URL from sandbox to production</li>
            <li>Test with small real amounts</li>
            <li>Configure webhook URLs in ToyyibPay dashboard</li>
            <li>Monitor payment logs and error handling</li>
        </ol>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>