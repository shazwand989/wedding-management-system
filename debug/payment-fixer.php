<?php
require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

echo "<h2>Payment Status Fixer</h2>";

$booking_id = $_GET['booking_id'] ?? 0;
$action = $_GET['action'] ?? '';

if ($booking_id && $action === 'refresh_status') {
    echo "<h3>Refreshing Payment Status for Booking ID: $booking_id</h3>";
    
    // Get all ToyyibPay transactions for this booking
    $stmt = $pdo->prepare("SELECT * FROM toyyibpay_transactions WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $transactions = $stmt->fetchAll();
    
    if (empty($transactions)) {
        echo "<p style='color: red;'>No ToyyibPay transactions found for this booking.</p>";
    } else {
        $toyyibpay = new ToyyibPay();
        
        foreach ($transactions as $trans) {
            echo "<h4>Checking Bill Code: " . $trans['bill_code'] . "</h4>";
            
            // Get latest status from ToyyibPay
            $api_transactions = $toyyibpay->getBillTransactions($trans['bill_code']);
            
            if ($api_transactions) {
                foreach ($api_transactions as $api_trans) {
                    if ($api_trans['billcode'] === $trans['bill_code']) {
                        echo "<p><strong>API Status:</strong> " . $api_trans['billpaymentStatus'] . "</p>";
                        echo "<p><strong>Local Status:</strong> " . $trans['status'] . "</p>";
                        
                        if ($api_trans['billpaymentStatus'] == '1' && $trans['status'] !== 'successful') {
                            echo "<div style='background: #ffffcc; padding: 10px; margin: 10px 0; border: 1px solid #ffcc00;'>";
                            echo "<strong>⚠️ PAYMENT MISMATCH DETECTED!</strong><br>";
                            echo "ToyyibPay shows payment as successful, but local status is: " . $trans['status'] . "<br>";
                            echo "<a href='?booking_id=$booking_id&action=force_update&bill_code=" . $trans['bill_code'] . "' style='color: red; font-weight: bold;'>Click here to force update payment status</a>";
                            echo "</div>";
                        } elseif ($api_trans['billpaymentStatus'] == '1' && $trans['status'] === 'successful') {
                            echo "<div style='color: green;'>✅ Payment status is correctly synchronized</div>";
                        } else {
                            echo "<div style='color: orange;'>⏳ Payment not completed yet on ToyyibPay side</div>";
                        }
                        break;
                    }
                }
            } else {
                echo "<p style='color: red;'>Could not fetch transaction details from ToyyibPay API</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<p><a href='?booking_id=$booking_id'>← Back to Booking Analysis</a></p>";
    
} elseif ($booking_id && $action === 'force_update' && !empty($_GET['bill_code'])) {
    $bill_code = $_GET['bill_code'];
    
    echo "<h3>Force Updating Payment Status</h3>";
    echo "<p><strong>Booking ID:</strong> $booking_id</p>";
    echo "<p><strong>Bill Code:</strong> $bill_code</p>";
    
    try {
        $toyyibpay = new ToyyibPay();
        
        // Get transaction details
        $transactions = $toyyibpay->getBillTransactions($bill_code);
        
        if ($transactions) {
            foreach ($transactions as $trans) {
                if ($trans['billcode'] === $bill_code && $trans['billpaymentStatus'] == '1') {
                    // Simulate callback data
                    $callbackData = [
                        'billcode' => $bill_code,
                        'order_id' => $trans['billExternalReferenceNo'] ?? $booking_id,
                        'status_id' => '1'
                    ];
                    
                    echo "<h4>Processing Payment...</h4>";
                    $result = $toyyibpay->processCallback($callbackData, $pdo);
                    
                    if ($result['success']) {
                        echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                        echo "✅ SUCCESS! Payment has been processed and booking status updated.";
                        echo "</div>";
                        
                        echo "<p><strong>What was updated:</strong></p>";
                        echo "<ul>";
                        echo "<li>Payment record added to database</li>";
                        echo "<li>Booking paid_amount updated</li>";
                        echo "<li>Booking payment_status updated</li>";
                        echo "</ul>";
                        
                        // Show updated booking status
                        $stmt = $pdo->prepare("SELECT total_amount, paid_amount, payment_status FROM bookings WHERE id = ?");
                        $stmt->execute([$booking_id]);
                        $booking = $stmt->fetch();
                        
                        if ($booking) {
                            echo "<h4>Updated Booking Status:</h4>";
                            echo "<p><strong>Total Amount:</strong> RM " . number_format($booking['total_amount'], 2) . "</p>";
                            echo "<p><strong>Paid Amount:</strong> RM " . number_format($booking['paid_amount'], 2) . "</p>";
                            echo "<p><strong>Payment Status:</strong> " . $booking['payment_status'] . "</p>";
                            $remaining = $booking['total_amount'] - $booking['paid_amount'];
                            echo "<p><strong>Remaining:</strong> RM " . number_format($remaining, 2) . "</p>";
                        }
                        
                    } else {
                        echo "<div style='color: red; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                        echo "❌ ERROR: " . $result['message'];
                        echo "</div>";
                    }
                    break;
                }
            }
        } else {
            throw new Exception("Could not fetch transaction details from ToyyibPay");
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "❌ EXCEPTION: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<p><a href='?booking_id=$booking_id'>← Back to Booking Analysis</a></p>";
    
} elseif ($booking_id) {
    // Show booking analysis
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die("Booking not found");
    }
    
    echo "<h3>Booking Analysis - ID: $booking_id</h3>";
    
    $remaining = $booking['total_amount'] - $booking['paid_amount'];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Current Status:</h4>";
    echo "<p><strong>Total Amount:</strong> RM " . number_format($booking['total_amount'], 2) . "</p>";
    echo "<p><strong>Paid Amount:</strong> RM " . number_format($booking['paid_amount'], 2) . "</p>";
    echo "<p><strong>Remaining Amount:</strong> RM " . number_format($remaining, 2) . "</p>";
    echo "<p><strong>Payment Status:</strong> <span style='color: " . ($booking['payment_status'] === 'paid' ? 'green' : 'orange') . "; font-weight: bold;'>" . strtoupper($booking['payment_status']) . "</span></p>";
    echo "<p><strong>Booking Status:</strong> " . strtoupper($booking['booking_status']) . "</p>";
    echo "</div>";
    
    if ($remaining <= 0 || $booking['payment_status'] === 'paid') {
        echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "✅ This booking is fully paid. Payment page should not show.";
        echo "</div>";
    } else {
        echo "<div style='color: orange; font-weight: bold; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
        echo "⚠️ This booking has an outstanding balance. Payment page should show.";
        echo "</div>";
        
        echo "<p><a href='?booking_id=$booking_id&action=refresh_status' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔄 Check ToyyibPay Status</a></p>";
    }
    
    // Show recent payments
    echo "<h4>Payment Records:</h4>";
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll();
    
    if (empty($payments)) {
        echo "<p style='color: #666;'>No payments recorded for this booking</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th></tr>";
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>RM " . number_format($payment['amount'], 2) . "</td>";
            echo "<td>" . $payment['payment_method'] . "</td>";
            echo "<td>" . $payment['status'] . "</td>";
            echo "<td>" . ($payment['transaction_id'] ?: 'N/A') . "</td>";
            echo "<td>" . $payment['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show ToyyibPay transactions
    echo "<h4>ToyyibPay Transactions:</h4>";
    $stmt = $pdo->prepare("SELECT * FROM toyyibpay_transactions WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->execute([$booking_id]);
    $toyyib_transactions = $stmt->fetchAll();
    
    if (empty($toyyib_transactions)) {
        echo "<p style='color: #666;'>No ToyyibPay transactions for this booking</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Bill Code</th><th>Amount</th><th>Status</th><th>Created</th><th>Actions</th></tr>";
        foreach ($toyyib_transactions as $trans) {
            echo "<tr>";
            echo "<td>" . $trans['bill_code'] . "</td>";
            echo "<td>RM " . number_format($trans['amount'], 2) . "</td>";
            echo "<td>" . $trans['status'] . "</td>";
            echo "<td>" . $trans['created_at'] . "</td>";
            echo "<td><a href='../debug/toyyibpay-status.php?bill_code=" . $trans['bill_code'] . "'>Check Status</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<form method='GET'>";
    echo "<p>Enter Booking ID to analyze: <input type='number' name='booking_id' placeholder='e.g., 1000' required></p>";
    echo "<button type='submit'>Analyze Booking</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<h3>Quick Links to Test Bookings:</h3>";
    echo "<p><a href='?booking_id=999'>Check Booking #999</a></p>";
    echo "<p><a href='?booking_id=1000'>Check Booking #1000</a></p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>