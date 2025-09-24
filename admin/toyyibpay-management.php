<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

define('ADMIN_ACCESS', true);

$page_header = 'ToyyibPay Management';
$page_description = 'Manage ToyyibPay integration and transactions';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $toyyibpay = new ToyyibPay();

        switch ($_POST['action']) {
            case 'verify_bill':
                $billCode = trim($_POST['bill_code']);
                if (empty($billCode)) {
                    throw new Exception('Bill code is required');
                }

                $transactions = $toyyibpay->getBillTransactions($billCode);
                if ($transactions === false) {
                    throw new Exception('Failed to fetch bill transactions');
                }

                echo json_encode([
                    'success' => true,
                    'transactions' => $transactions
                ]);
                exit();

            case 'sync_payment':
                $billCode = trim($_POST['bill_code']);
                if (empty($billCode)) {
                    throw new Exception('Bill code is required');
                }

                // Get transactions from ToyyibPay
                $transactions = $toyyibpay->getBillTransactions($billCode);
                if ($transactions === false || empty($transactions)) {
                    throw new Exception('No transactions found for this bill code');
                }

                // Find successful transaction
                $successTransaction = null;
                foreach ($transactions as $trans) {
                    if ($trans['billcode'] === $billCode && $trans['billpaymentStatus'] == '1') {
                        $successTransaction = $trans;
                        break;
                    }
                }

                if (!$successTransaction) {
                    throw new Exception('No successful payment found for this bill code');
                }

                // Process the payment manually
                $callbackData = [
                    'billcode' => $billCode,
                    'order_id' => $successTransaction['billExternalReferenceNo'] ?? '',
                ];

                $result = $toyyibpay->processCallback($callbackData, $pdo);
                
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment synchronized successfully'
                    ]);
                } else {
                    throw new Exception($result['message'] ?? 'Failed to sync payment');
                }
                exit();
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Get ToyyibPay related payments
$toyyibpay_payments_query = "
    SELECT p.*, b.id as booking_id, b.event_date, b.total_amount as booking_total,
           u.full_name as customer_name, u.email as customer_email
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN users u ON b.customer_id = u.id
    WHERE p.payment_method = 'online' AND p.notes LIKE '%ToyyibPay%'
    ORDER BY p.created_at DESC
    LIMIT 50
";

$toyyibpay_payments = $pdo->query($toyyibpay_payments_query)->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_toyyibpay_payments,
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_completed_amount,
        COUNT(CASE WHEN DATE(p.created_at) = CURDATE() AND p.status = 'completed' THEN 1 END) as today_completed,
        COALESCE(SUM(CASE WHEN DATE(p.created_at) = CURDATE() AND p.status = 'completed' THEN p.amount ELSE 0 END), 0) as today_amount
    FROM payments p
    WHERE p.payment_method = 'online' AND p.notes LIKE '%ToyyibPay%'
";
$toyyibpay_stats = $pdo->query($stats_query)->fetch();

include 'layouts/header.php';
?>

<div class="container-fluid">

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $toyyibpay_stats['total_toyyibpay_payments']; ?></h4>
                            <p class="mb-0">Total ToyyibPay Payments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>RM <?php echo number_format($toyyibpay_stats['total_completed_amount'], 2); ?></h4>
                            <p class="mb-0">Total Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $toyyibpay_stats['today_completed']; ?></h4>
                            <p class="mb-0">Today's Payments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>RM <?php echo number_format($toyyibpay_stats['today_amount'], 2); ?></h4>
                            <p class="mb-0">Today's Revenue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Tools -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search"></i> Verify ToyyibPay Bill</h5>
                </div>
                <div class="card-body">
                    <form id="verifyBillForm">
                        <div class="form-group">
                            <label for="bill_code">Bill Code</label>
                            <input type="text" class="form-control" id="bill_code" name="bill_code" 
                                   placeholder="Enter ToyyibPay bill code" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Verify Bill
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sync"></i> Sync Payment</h5>
                </div>
                <div class="card-body">
                    <form id="syncPaymentForm">
                        <div class="form-group">
                            <label for="sync_bill_code">Bill Code</label>
                            <input type="text" class="form-control" id="sync_bill_code" name="bill_code" 
                                   placeholder="Enter bill code to sync" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-sync"></i> Sync Payment
                        </button>
                    </form>
                    <small class="form-text text-muted">
                        Use this to manually sync a payment that was completed in ToyyibPay but not reflected in the system.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- ToyyibPay Payments -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent ToyyibPay Payments</h5>
            <a href="payments.php?method=online" class="btn btn-sm btn-primary">
                <i class="fas fa-list"></i> View All Online Payments
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($toyyibpay_payments)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                    <h5>No ToyyibPay Payments Found</h5>
                    <p class="text-muted">No payments through ToyyibPay have been recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($toyyibpay_payments as $payment): ?>
                                <tr>
                                    <td>#<?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="edit_booking.php?id=<?php echo $payment['booking_id']; ?>">
                                            #<?php echo $payment['booking_id']; ?>
                                        </a><br>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($payment['event_date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['customer_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong>RM <?php echo number_format($payment['amount'], 2); ?></strong><br>
                                        <small class="text-muted">of RM <?php echo number_format($payment['booking_total'], 2); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($payment['transaction_id']): ?>
                                            <code class="small"><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php
                                            echo $payment['status'] === 'completed' ? 'success' : 
                                                ($payment['status'] === 'pending' ? 'warning' : 
                                                ($payment['status'] === 'failed' ? 'danger' : 'secondary'));
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Configuration Guide -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> ToyyibPay Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Required Configuration</h6>
                    <ul class="small">
                        <li>Update secret key and category code in <code>includes/toyyibpay.php</code></li>
                        <li>Set callback URL in ToyyibPay dashboard to: <br>
                            <code><?php echo SITE_URL; ?>includes/toyyibpay-callback.php</code></li>
                        <li>Set return URL to: <br>
                            <code><?php echo SITE_URL; ?>customer/payment-return.php</code></li>
                        <li>Generate and set a secure callback secret</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Testing</h6>
                    <ul class="small">
                        <li>Use sandbox URL for testing: <code>https://dev.toyyibpay.com</code></li>
                        <li>Switch to production URL: <code>https://toyyibpay.com</code></li>
                        <li>Test with small amounts first</li>
                        <li>Verify callback handling is working correctly</li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Security Note:</strong> Make sure to update the configuration with your actual 
                ToyyibPay credentials and never commit sensitive keys to version control.
            </div>
        </div>
    </div>

</div>

<!-- Bill Verification Modal -->
<div class="modal fade" id="billVerificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bill Verification Result</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="billVerificationContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#verifyBillForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
        
        $.ajax({
            url: 'toyyibpay-management.php',
            method: 'POST',
            data: $(this).serialize() + '&action=verify_bill',
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                let html = '<h6>Bill Transactions</h6>';
                
                if (data.transactions.length === 0) {
                    html += '<p class="text-muted">No transactions found for this bill code.</p>';
                } else {
                    html += '<div class="table-responsive"><table class="table table-sm">';
                    html += '<thead><tr><th>Bill Code</th><th>Status</th><th>Amount</th><th>Date</th><th>Transaction ID</th></tr></thead><tbody>';
                    
                    data.transactions.forEach(function(trans) {
                        const status = trans.billpaymentStatus == '1' ? 'Successful' : 
                                     trans.billpaymentStatus == '2' ? 'Pending' : 'Failed';
                        const statusClass = trans.billpaymentStatus == '1' ? 'success' : 
                                          trans.billpaymentStatus == '2' ? 'warning' : 'danger';
                        
                        html += '<tr>';
                        html += '<td>' + trans.billcode + '</td>';
                        html += '<td><span class="badge badge-' + statusClass + '">' + status + '</span></td>';
                        html += '<td>RM ' + (trans.billpaymentAmount / 100).toFixed(2) + '</td>';
                        html += '<td>' + (trans.billpaymentDate || '-') + '</td>';
                        html += '<td>' + (trans.billpaymentTransactionId || '-') + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                }
                
                $('#billVerificationContent').html(html);
                $('#billVerificationModal').modal('show');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .fail(function() {
            alert('Failed to verify bill. Please try again.');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });

    $('#syncPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
        
        if (!confirm('Are you sure you want to sync this payment? This action cannot be undone.')) {
            $btn.prop('disabled', false).html(originalText);
            return;
        }
        
        $.ajax({
            url: 'toyyibpay-management.php',
            method: 'POST',
            data: $(this).serialize() + '&action=sync_payment',
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                alert('Payment synchronized successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .fail(function() {
            alert('Failed to sync payment. Please try again.');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
});

function viewPaymentDetails(paymentId) {
    window.open('payments.php#payment-' + paymentId, '_blank');
}
</script>
