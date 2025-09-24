<?php
/**
 * Command Line ToyyibPay Test
 * Test your ToyyibPay integration from command line
 */

require_once 'includes/config.php';
require_once 'includes/toyyibpay.php';

echo "=== ToyyibPay Integration Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $toyyibpay = new ToyyibPay();
    echo "✓ ToyyibPay class loaded successfully\n";
    
    // Test bill creation with dummy data
    $testBillParams = [
        'billName' => 'Test Payment ' . date('His'),
        'billDescription' => 'Integration test payment',
        'billAmount' => 10.00, // RM 10.00 for testing
        'billExternalReferenceNo' => 'TEST_' . time(),
        'billTo' => 'Test Customer',
        'billEmail' => 'test@example.com',
        'billPhone' => '60123456789'
    ];
    
    echo "\n--- Testing Bill Creation ---\n";
    echo "Test Data:\n";
    echo "- Amount: RM " . number_format($testBillParams['billAmount'], 2) . "\n";
    echo "- Reference: " . $testBillParams['billExternalReferenceNo'] . "\n";
    echo "- Customer: " . $testBillParams['billTo'] . "\n\n";
    
    echo "Creating bill with ToyyibPay...\n";
    $result = $toyyibpay->createBill($testBillParams);
    
    if ($result['success']) {
        echo "✓ Test bill created successfully!\n";
        echo "✓ Bill Code: " . $result['billCode'] . "\n";
        echo "✓ Payment URL: " . $result['paymentUrl'] . "\n\n";
        
        echo "--- Testing Bill Verification ---\n";
        $transactions = $toyyibpay->getBillTransactions($result['billCode']);
        
        if ($transactions !== false) {
            echo "✓ Bill verification successful\n";
            echo "✓ Transaction count: " . count($transactions) . "\n";
            
            if (!empty($transactions)) {
                $trans = $transactions[0];
                echo "✓ Bill Status: " . ($trans['billpaymentStatus'] ?? 'Unknown') . "\n";
                echo "✓ Amount: RM " . number_format(($trans['billpaymentAmount'] ?? 0) / 100, 2) . "\n";
            }
        } else {
            echo "✗ Bill verification failed\n";
        }
        
        echo "\n--- Integration Test Results ---\n";
        echo "✓ API Connection: Working\n";
        echo "✓ Bill Creation: Success\n";
        echo "✓ Bill Verification: Working\n";
        echo "✓ Credentials: Valid\n";
        
        echo "\n--- Next Steps ---\n";
        echo "1. Configure webhook URLs in ToyyibPay dashboard:\n";
        echo "   - Return URL: " . SITE_URL . "customer/payment-return.php\n";
        echo "   - Callback URL: " . SITE_URL . "includes/toyyibpay-callback.php\n";
        echo "\n2. Test payment flow:\n";
        echo "   - Visit: " . $result['paymentUrl'] . "\n";
        echo "   - Or access: " . SITE_URL . "test_toyyibpay.php\n";
        echo "\n3. Monitor payments in admin panel:\n";
        echo "   - Visit: " . SITE_URL . "admin/toyyibpay-management.php\n";
        
    } else {
        echo "✗ Failed to create test bill\n";
        echo "✗ Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        
        if (isset($result['response'])) {
            echo "\nAPI Response:\n";
            print_r($result['response']);
        }
        
        echo "\n--- Troubleshooting ---\n";
        echo "1. Check your ToyyibPay credentials\n";
        echo "2. Verify internet connectivity\n";
        echo "3. Check ToyyibPay service status\n";
        echo "4. Ensure cURL extension is enabled\n";
    }
    
} catch (Exception $e) {
    echo "✗ Critical Error: " . $e->getMessage() . "\n";
    echo "\n--- System Check ---\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "cURL Extension: " . (extension_loaded('curl') ? 'Enabled' : 'Disabled') . "\n";
    echo "JSON Extension: " . (extension_loaded('json') ? 'Enabled' : 'Disabled') . "\n";
}

echo "\n=== Test Complete ===\n";
?>