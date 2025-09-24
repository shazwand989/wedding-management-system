<?php
/**
 * Manual Payment Status Update - Testing Tool
 * Use this to test payment status updates without going through ToyyibPay
 */

require_once '../includes/config.php';

echo "<h2>🧪 Manual Payment Status Update (Testing)</h2>";

$action = $_GET['action'] ?? '';
$booking_id = $_GET['booking_id'] ?? 0;

if ($action === 'simulate_payment' && $booking_id) {
    echo "<h3>Simulating Successful Payment for Booking #$booking_id</h3>";
    
    try {
        // Get current booking details
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Before Update:</h4>";
        echo "<p><strong>Total Amount:</strong> RM " . number_format($booking['total_amount'], 2) . "</p>";
        echo "<p><strong>Paid Amount:</strong> RM " . number_format($booking['paid_amount'], 2) . "</p>";
        echo "<p><strong>Payment Status:</strong> " . $booking['payment_status'] . "</p>";
        echo "<p><strong>Booking Status:</strong> " . $booking['booking_status'] . "</p>";
        echo "</div>";
        
        // Simulate a full payment
        $payment_amount = $booking['total_amount'] - $booking['paid_amount'];
        
        if ($payment_amount <= 0) {
            throw new Exception("Booking is already fully paid");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_date, status, notes)
            VALUES (?, ?, 'toyyibpay', ?, NOW(), 'completed', ?)
        ");
        $transaction_id = 'TEST_' . time() . '_' . $booking_id;
        $stmt->execute([
            $booking_id,
            $payment_amount,
            $transaction_id,
            'Simulated payment for testing - Full payment of RM ' . number_format($payment_amount, 2)
        ]);
        
        // Update booking
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET paid_amount = paid_amount + ?, 
                payment_status = CASE 
                    WHEN paid_amount + ? >= total_amount THEN 'paid'
                    ELSE 'partial'
                END,
                booking_status = CASE 
                    WHEN booking_status = 'pending' AND paid_amount + ? >= total_amount THEN 'confirmed'
                    ELSE booking_status
                END
            WHERE id = ?
        ");
        $stmt->execute([$payment_amount, $payment_amount, $payment_amount, $booking_id]);
        
        // Insert simulated ToyyibPay transaction
        $bill_code = 'TEST' . time();
        $stmt = $pdo->prepare("
            INSERT INTO toyyibpay_transactions (bill_code, booking_id, amount, status, transaction_id, created_at, updated_at)
            VALUES (?, ?, ?, 'successful', ?, NOW(), NOW())
        ");
        $stmt->execute([$bill_code, $booking_id, $payment_amount, $transaction_id]);
        
        $pdo->commit();
        
        // Get updated booking details
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $updated_booking = $stmt->fetch();
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #c3e6cb;'>";
        echo "<h4 style='color: green;'>✅ After Update (SUCCESSFUL!):</h4>";
        echo "<p><strong>Total Amount:</strong> RM " . number_format($updated_booking['total_amount'], 2) . "</p>";
        echo "<p><strong>Paid Amount:</strong> RM " . number_format($updated_booking['paid_amount'], 2) . "</p>";
        echo "<p><strong>Payment Status:</strong> <span style='color: green; font-weight: bold;'>" . strtoupper($updated_booking['payment_status']) . "</span></p>";
        echo "<p><strong>Booking Status:</strong> <span style='color: green; font-weight: bold;'>" . strtoupper($updated_booking['booking_status']) . "</span></p>";
        echo "<p><strong>Transaction ID:</strong> " . $transaction_id . "</p>";
        echo "<p><strong>Bill Code:</strong> " . $bill_code . "</p>";
        echo "</div>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #ffeaa7;'>";
        echo "<h4>🎉 Payment Simulation Complete!</h4>";
        echo "<p><strong>What happened:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Payment record added to database</li>";
        echo "<li>✅ Booking paid_amount updated</li>";
        echo "<li>✅ Payment status changed to 'paid'</li>";
        echo "<li>✅ Booking status updated (if applicable)</li>";
        echo "<li>✅ ToyyibPay transaction record created</li>";
        echo "</ul>";
        echo "<p><strong>Result:</strong> The Pay button should now disappear from the bookings page!</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin: 20px 0;'>";
        echo "<a href='https://shazwan-danial.my/wedding-management-system/customer/bookings.php' target='_blank' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
        echo "🔍 Check Bookings Page Now";
        echo "</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
        echo "<h4 style='color: red;'>❌ Error:</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<p><a href='?' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Menu</a></p>";
    
} else {
    // Show menu
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
    echo "<p>This tool simulates a successful payment to test if the payment status updates correctly.</p>";
    echo "<p><strong>⚠️ WARNING:</strong> This is for testing purposes only. Do not use on live bookings with real payments.</p>";
    echo "</div>";
    
    // Show bookings that can be tested
    echo "<h3>📋 Available Test Bookings:</h3>";
    
    $stmt = $pdo->query("
        SELECT id, total_amount, paid_amount, payment_status, booking_status, event_date 
        FROM bookings 
        WHERE total_amount > paid_amount 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $bookings = $stmt->fetchAll();
    
    if (empty($bookings)) {
        echo "<p>No bookings with outstanding balances found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Booking ID</th><th>Event Date</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($bookings as $booking) {
            $outstanding = $booking['total_amount'] - $booking['paid_amount'];
            echo "<tr>";
            echo "<td>#" . $booking['id'] . "</td>";
            echo "<td>" . date('M j, Y', strtotime($booking['event_date'])) . "</td>";
            echo "<td>RM " . number_format($booking['total_amount'], 2) . "</td>";
            echo "<td>RM " . number_format($booking['paid_amount'], 2) . "</td>";
            echo "<td style='color: red;'>RM " . number_format($outstanding, 2) . "</td>";
            echo "<td>" . $booking['payment_status'] . "</td>";
            echo "<td>";
            echo "<a href='?action=simulate_payment&booking_id=" . $booking['id'] . "' ";
            echo "onclick='return confirm(\"Simulate payment for booking #" . $booking['id'] . "?\")' ";
            echo "style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;'>";
            echo "💳 Simulate Payment";
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔧 How This Works:</h4>";
    echo "<ol>";
    echo "<li>Click 'Simulate Payment' for a booking</li>";
    echo "<li>The tool will create a payment record for the full outstanding amount</li>";
    echo "<li>It will update the booking's payment status to 'paid'</li>";
    echo "<li>The Pay button should disappear from the customer bookings page</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3, h4 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    a { text-decoration: none; }
    a:hover { opacity: 0.8; }
</style>