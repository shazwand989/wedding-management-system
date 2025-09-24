<?php
require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

echo "<h2>ToyyibPay Manual Payment Verification</h2>";

$bill_code = $_GET['bill_code'] ?? '';

if ($bill_code) {
    echo "<h3>Checking Bill Code: $bill_code</h3>";
    
    $toyyibpay = new ToyyibPay();
    $transactions = $toyyibpay->getBillTransactions($bill_code);
    
    if ($transactions) {
        echo "<h4>Transaction Details:</h4>";
        echo "<pre>" . json_encode($transactions, JSON_PRETTY_PRINT) . "</pre>";
        
        foreach ($transactions as $trans) {
            if ($trans['billcode'] === $bill_code) {
                echo "<h4>Payment Status Analysis:</h4>";
                echo "<p><strong>Bill Code:</strong> " . $trans['billcode'] . "</p>";
                echo "<p><strong>Payment Status:</strong> " . $trans['billpaymentStatus'] . "</p>";
                echo "<p><strong>Payment Date:</strong> " . ($trans['billpaymentDate'] ?? 'N/A') . "</p>";
                echo "<p><strong>Amount:</strong> RM " . number_format(($trans['billpaymentAmount'] ?? 0) / 100, 2) . "</p>";
                echo "<p><strong>Transaction ID:</strong> " . ($trans['billpaymentTransactionId'] ?? 'N/A') . "</p>";
                
                if ($trans['billpaymentStatus'] == '1') {
                    echo "<div style='color: green; font-weight: bold;'>✅ PAYMENT SUCCESSFUL</div>";
                    
                    // Try to manually process this payment
                    echo "<h4>Manual Payment Processing:</h4>";
                    try {
                        $callbackData = [
                            'billcode' => $bill_code,
                            'order_id' => $trans['billExternalReferenceNo'] ?? '',
                            'status_id' => '1'
                        ];
                        
                        $result = $toyyibpay->processCallback($callbackData, $pdo);
                        if ($result['success']) {
                            echo "<div style='color: green;'>✅ Payment processed successfully in database</div>";
                        } else {
                            echo "<div style='color: red;'>❌ Error processing payment: " . $result['message'] . "</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div style='color: red;'>❌ Exception: " . $e->getMessage() . "</div>";
                    }
                } else {
                    echo "<div style='color: orange; font-weight: bold;'>⏳ Payment not completed yet</div>";
                }
                break;
            }
        }
    } else {
        echo "<p style='color: red;'>❌ No transactions found for this bill code</p>";
    }
} else {
    echo "<form method='GET'>";
    echo "<p>Enter Bill Code to check: <input type='text' name='bill_code' placeholder='e.g., abc123def' required></p>";
    echo "<button type='submit'>Check Payment Status</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<h3>Recent ToyyibPay Transactions:</h3>";
    
    $stmt = $pdo->query("SELECT * FROM toyyibpay_transactions ORDER BY created_at DESC LIMIT 10");
    $recent_transactions = $stmt->fetchAll();
    
    if (empty($recent_transactions)) {
        echo "<p>No recent transactions found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Bill Code</th><th>Booking ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Actions</th></tr>";
        foreach ($recent_transactions as $trans) {
            echo "<tr>";
            echo "<td>" . $trans['bill_code'] . "</td>";
            echo "<td>" . $trans['booking_id'] . "</td>";
            echo "<td>RM " . number_format($trans['amount'], 2) . "</td>";
            echo "<td>" . $trans['status'] . "</td>";
            echo "<td>" . $trans['created_at'] . "</td>";
            echo "<td><a href='?bill_code=" . $trans['bill_code'] . "'>Check Status</a></td>";
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
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
</style>