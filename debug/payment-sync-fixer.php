<?php
/**
 * Quick Payment Status Synchronizer
 * This script helps fix payment status discrepancies
 */

require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

echo "<h2>Payment Status Synchronizer</h2>";
echo "<p><strong>This tool will check and fix payment statuses that may be out of sync.</strong></p>";

$action = $_GET['action'] ?? '';
$booking_id = $_GET['booking_id'] ?? 0;

if ($action === 'fix_all') {
    echo "<h3>🔧 Fixing All Payment Discrepancies</h3>";
    
    try {
        // Find all ToyyibPay transactions that might need processing
        $stmt = $pdo->query("
            SELECT DISTINCT tt.booking_id, tt.bill_code, tt.amount, tt.status as toyyib_status,
                   b.total_amount, b.paid_amount, b.payment_status, b.booking_status
            FROM toyyibpay_transactions tt
            JOIN bookings b ON tt.booking_id = b.id
            WHERE tt.status IN ('pending', 'successful')
            ORDER BY tt.created_at DESC
        ");
        $transactions = $stmt->fetchAll();
        
        if (empty($transactions)) {
            echo "<p style='color: orange;'>No ToyyibPay transactions found to process.</p>";
        } else {
            $toyyibpay = new ToyyibPay();
            $fixed_count = 0;
            
            foreach ($transactions as $trans) {
                echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px;'>";
                echo "<h4>Processing Booking #{$trans['booking_id']} - Bill Code: {$trans['bill_code']}</h4>";
                
                // Check current ToyyibPay status
                $api_transactions = $toyyibpay->getBillTransactions($trans['bill_code']);
                
                if ($api_transactions) {
                    foreach ($api_transactions as $api_trans) {
                        if (($api_trans['billcode'] ?? '') === $trans['bill_code'] && 
                            ($api_trans['billpaymentStatus'] ?? '0') == '1') {
                            
                            echo "<p style='color: green;'>✅ ToyyibPay shows payment as SUCCESSFUL</p>";
                            
                            // Check if local payment record exists
                            $check_stmt = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ? AND transaction_id = ?");
                            $transaction_id = $api_trans['billpaymentTransactionId'] ?? 'SYNC_' . time();
                            $check_stmt->execute([$trans['booking_id'], $transaction_id]);
                            
                            if ($check_stmt->fetch()) {
                                echo "<p style='color: blue;'>ℹ️ Payment record already exists</p>";
                            } else {
                                // Process the payment
                                $callbackData = [
                                    'billcode' => $trans['bill_code'],
                                    'order_id' => $trans['booking_id']
                                ];
                                
                                $result = $toyyibpay->processCallback($callbackData, $pdo);
                                
                                if ($result['success']) {
                                    echo "<p style='color: green; font-weight: bold;'>✅ FIXED! Payment processed successfully</p>";
                                    $fixed_count++;
                                } else {
                                    echo "<p style='color: red;'>❌ Error: " . $result['message'] . "</p>";
                                }
                            }
                            
                            break;
                        }
                    }
                } else {
                    echo "<p style='color: red;'>❌ Could not fetch data from ToyyibPay API</p>";
                }
                
                echo "</div>";
            }
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3 style='color: green;'>Summary: Fixed $fixed_count payment(s)</h3>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='?' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Main Menu</a></p>";
    
} elseif ($action === 'check_booking' && $booking_id) {
    echo "<h3>🔍 Checking Booking #$booking_id</h3>";
    
    // Get booking details
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo "<p style='color: red;'>Booking not found!</p>";
    } else {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Current Booking Status:</h4>";
        echo "<p><strong>Total Amount:</strong> RM " . number_format($booking['total_amount'], 2) . "</p>";
        echo "<p><strong>Paid Amount:</strong> RM " . number_format($booking['paid_amount'], 2) . "</p>";
        echo "<p><strong>Remaining:</strong> RM " . number_format($booking['total_amount'] - $booking['paid_amount'], 2) . "</p>";
        echo "<p><strong>Payment Status:</strong> " . strtoupper($booking['payment_status']) . "</p>";
        echo "<p><strong>Booking Status:</strong> " . strtoupper($booking['booking_status']) . "</p>";
        echo "</div>";
        
        // Check payments
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
        $stmt->execute([$booking_id]);
        $payments = $stmt->fetchAll();
        
        echo "<h4>Payment Records:</h4>";
        if (empty($payments)) {
            echo "<p>No payment records found.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th></tr>";
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
        
        // Check ToyyibPay transactions
        $stmt = $pdo->prepare("SELECT * FROM toyyibpay_transactions WHERE booking_id = ? ORDER BY created_at DESC");
        $stmt->execute([$booking_id]);
        $toyyib_trans = $stmt->fetchAll();
        
        echo "<h4>ToyyibPay Transactions:</h4>";
        if (empty($toyyib_trans)) {
            echo "<p>No ToyyibPay transactions found.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Bill Code</th><th>Amount</th><th>Status</th><th>Created</th><th>Actions</th></tr>";
            foreach ($toyyib_trans as $trans) {
                echo "<tr>";
                echo "<td>" . $trans['bill_code'] . "</td>";
                echo "<td>RM " . number_format($trans['amount'], 2) . "</td>";
                echo "<td>" . $trans['status'] . "</td>";
                echo "<td>" . $trans['created_at'] . "</td>";
                echo "<td><a href='../debug/toyyibpay-status.php?bill_code=" . $trans['bill_code'] . "' target='_blank'>Check Status</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='?' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Main Menu</a></p>";
    
} else {
    // Main menu
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
    echo "<h3>🛠️ Available Actions:</h3>";
    echo "<div style='margin: 15px 0;'>";
    echo "<a href='?action=fix_all' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>";
    echo "🔧 Fix All Payment Discrepancies";
    echo "</a>";
    echo "</div>";
    
    echo "<div style='margin: 15px 0;'>";
    echo "<form method='GET' style='display: inline-block;'>";
    echo "<input type='hidden' name='action' value='check_booking'>";
    echo "<input type='number' name='booking_id' placeholder='Enter Booking ID' required style='padding: 8px; margin-right: 10px;'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px;'>🔍 Check Specific Booking</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    
    // Show recent bookings with payment issues
    echo "<h3>📋 Bookings with Potential Payment Issues:</h3>";
    
    $stmt = $pdo->query("
        SELECT b.id, b.total_amount, b.paid_amount, b.payment_status, b.booking_status, u.full_name
        FROM bookings b
        JOIN users u ON b.customer_id = u.id
        WHERE (b.total_amount > b.paid_amount AND b.payment_status != 'paid') 
           OR (b.paid_amount > 0 AND b.payment_status = 'pending')
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $problem_bookings = $stmt->fetchAll();
    
    if (empty($problem_bookings)) {
        echo "<p style='color: green;'>✅ No payment issues detected!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Booking ID</th><th>Customer</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Status</th><th>Actions</th></tr>";
        foreach ($problem_bookings as $booking) {
            $remaining = $booking['total_amount'] - $booking['paid_amount'];
            echo "<tr>";
            echo "<td>#" . $booking['id'] . "</td>";
            echo "<td>" . htmlspecialchars($booking['full_name']) . "</td>";
            echo "<td>RM " . number_format($booking['total_amount'], 2) . "</td>";
            echo "<td>RM " . number_format($booking['paid_amount'], 2) . "</td>";
            echo "<td style='color: " . ($remaining > 0 ? 'red' : 'green') . ";'>RM " . number_format($remaining, 2) . "</td>";
            echo "<td>" . $booking['payment_status'] . "</td>";
            echo "<td><a href='?action=check_booking&booking_id=" . $booking['id'] . "'>Check</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
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