<?php
require_once '../includes/config.php';

// Debug payment status for a specific booking
$booking_id = $_GET['booking_id'] ?? 0;

if (!$booking_id) {
    die('Please provide booking_id parameter');
}

echo "<h2>Booking Payment Debug - Booking ID: $booking_id</h2>";

// Get booking details
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found");
}

echo "<h3>Booking Details:</h3>";
echo "<table border='1'>";
foreach ($booking as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
}
echo "</table>";

$remaining = $booking['total_amount'] - $booking['paid_amount'];
echo "<p><strong>Calculated Remaining Amount: RM " . number_format($remaining, 2) . "</strong></p>";

// Get all payments for this booking
echo "<h3>Payment Records:</h3>";
$stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
$stmt->execute([$booking_id]);
$payments = $stmt->fetchAll();

if (empty($payments)) {
    echo "<p>No payments found for this booking</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th><th>Notes</th></tr>";
    foreach ($payments as $payment) {
        echo "<tr>";
        echo "<td>" . $payment['id'] . "</td>";
        echo "<td>RM " . number_format($payment['amount'], 2) . "</td>";
        echo "<td>" . $payment['payment_method'] . "</td>";
        echo "<td>" . $payment['status'] . "</td>";
        echo "<td>" . $payment['transaction_id'] . "</td>";
        echo "<td>" . $payment['payment_date'] . "</td>";
        echo "<td>" . $payment['notes'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Get ToyyibPay transactions
echo "<h3>ToyyibPay Transactions:</h3>";
$stmt = $pdo->prepare("SELECT * FROM toyyibpay_transactions WHERE booking_id = ? ORDER BY created_at DESC");
$stmt->execute([$booking_id]);
$toyyib_transactions = $stmt->fetchAll();

if (empty($toyyib_transactions)) {
    echo "<p>No ToyyibPay transactions found for this booking</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Bill Code</th><th>Amount</th><th>Status</th><th>Transaction ID</th><th>Created</th><th>Updated</th></tr>";
    foreach ($toyyib_transactions as $trans) {
        echo "<tr>";
        echo "<td>" . $trans['bill_code'] . "</td>";
        echo "<td>RM " . number_format($trans['amount'], 2) . "</td>";
        echo "<td>" . $trans['status'] . "</td>";
        echo "<td>" . $trans['transaction_id'] . "</td>";
        echo "<td>" . $trans['created_at'] . "</td>";
        echo "<td>" . $trans['updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if payment page should show
echo "<h3>Payment Page Logic:</h3>";
echo "<p><strong>Should show payment form:</strong> ";
if ($remaining <= 0 || $booking['payment_status'] === 'paid') {
    echo "NO - Booking is fully paid or marked as paid</p>";
} else {
    echo "YES - Remaining amount: RM " . number_format($remaining, 2) . "</p>";
}

echo "<p><strong>Booking Status:</strong> " . $booking['booking_status'] . "</p>";
echo "<p><strong>Payment Status:</strong> " . $booking['payment_status'] . "</p>";

echo "<hr>";
echo "<p><a href='../customer/payment.php?booking_id=$booking_id'>Go to Payment Page</a></p>";
echo "<p><a href='../customer/bookings.php'>Go to Bookings Page</a></p>";
?>