<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

$customer_id = $_SESSION['user_id'];
$page_title = 'Payment Result';

// Get parameters from URL
$status = $_GET['status_id'] ?? '';
$billcode = $_GET['billcode'] ?? '';
$order_id = $_GET['order_id'] ?? '';

$payment_status = 'unknown';
$message = '';
$booking_id = null;

// Check payment attempt from session
if (isset($_SESSION['payment_attempt'])) {
    $attempt = $_SESSION['payment_attempt'];
    $booking_id = $attempt['booking_id'];
    
    // Clear the session data
    unset($_SESSION['payment_attempt']);
}

// Determine payment status based on ToyyibPay response
if ($status == '1') {
    $payment_status = 'success';
    $message = 'Payment completed successfully!';
} elseif ($status == '2') {
    $payment_status = 'pending';
    $message = 'Payment is being processed. You will be notified once confirmed.';
} elseif ($status == '3') {
    $payment_status = 'failed';
    $message = 'Payment was not completed. Please try again.';
} else {
    $payment_status = 'unknown';
    $message = 'Payment status is unknown. Please contact support if you made a payment.';
}

// If we have a booking ID, get the latest booking information
$booking = null;
if ($booking_id) {
    $stmt = $pdo->prepare("
        SELECT b.*, wp.name as package_name
        FROM bookings b
        LEFT JOIN wedding_packages wp ON b.package_id = wp.id
        WHERE b.id = ? AND b.customer_id = ?
    ");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch();
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            
            <!-- Payment Status Card -->
            <div class="card">
                <div class="card-header text-center 
                    <?php echo $payment_status === 'success' ? 'bg-success text-white' : 
                               ($payment_status === 'pending' ? 'bg-warning text-white' : 
                               ($payment_status === 'failed' ? 'bg-danger text-white' : 'bg-secondary text-white')); ?>">
                    
                    <?php if ($payment_status === 'success'): ?>
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h4>Payment Successful!</h4>
                    <?php elseif ($payment_status === 'pending'): ?>
                        <i class="fas fa-clock fa-3x mb-3"></i>
                        <h4>Payment Pending</h4>
                    <?php elseif ($payment_status === 'failed'): ?>
                        <i class="fas fa-times-circle fa-3x mb-3"></i>
                        <h4>Payment Failed</h4>
                    <?php else: ?>
                        <i class="fas fa-question-circle fa-3x mb-3"></i>
                        <h4>Payment Status Unknown</h4>
                    <?php endif; ?>
                </div>
                
                <div class="card-body text-center">
                    <p class="lead"><?php echo htmlspecialchars($message); ?></p>
                    
                    <?php if ($billcode): ?>
                        <div class="alert alert-info">
                            <strong>Bill Code:</strong> <?php echo htmlspecialchars($billcode); ?>
                            <?php if ($order_id): ?>
                                <br><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($booking): ?>
                        <div class="mt-4">
                            <h5>Booking Information</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td><strong>Booking ID:</strong></td>
                                            <td>#<?php echo $booking['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Package:</strong></td>
                                            <td><?php echo htmlspecialchars($booking['package_name'] ?: 'Custom Package'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Event Date:</strong></td>
                                            <td><?php echo date('F j, Y', strtotime($booking['event_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Amount:</strong></td>
                                            <td>RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Paid Amount:</strong></td>
                                            <td>RM <?php echo number_format($booking['paid_amount'], 2); ?></td>
                                        </tr>
                                        <?php if ($booking['total_amount'] > $booking['paid_amount']): ?>
                                        <tr>
                                            <td><strong>Remaining:</strong></td>
                                            <td class="text-warning">
                                                RM <?php echo number_format($booking['total_amount'] - $booking['paid_amount'], 2); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <?php if ($payment_status === 'success'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle"></i>
                                <strong>What's Next?</strong><br>
                                Your payment has been processed successfully. You will receive a confirmation email shortly.
                                <?php if ($booking && $booking['total_amount'] > $booking['paid_amount']): ?>
                                    <br><br>You still have an outstanding balance. You can make additional payments anytime.
                                <?php endif; ?>
                            </div>
                        <?php elseif ($payment_status === 'pending'): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i>
                                <strong>Please Wait</strong><br>
                                Your payment is being processed by the bank. This may take a few minutes to a few hours.
                                You will be notified once the payment is confirmed.
                            </div>
                        <?php elseif ($payment_status === 'failed'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Payment Not Completed</strong><br>
                                Your payment was not processed. This could be due to insufficient funds, 
                                cancelled transaction, or technical issues. Please try again or contact your bank.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-question-circle"></i>
                                <strong>Need Help?</strong><br>
                                If you believe you made a payment but the status is unclear, 
                                please contact our support team with your bill code information.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-footer text-center">
                    <div class="btn-group">
                        <a href="bookings.php" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> View Bookings
                        </a>
                        
                        <?php if ($booking && $booking['total_amount'] > $booking['paid_amount']): ?>
                            <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success">
                                <i class="fas fa-credit-card"></i> Make Another Payment
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($payment_status === 'failed' || $payment_status === 'unknown'): ?>
                            <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-warning">
                                <i class="fas fa-redo"></i> Try Again
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            Need help? <a href="mailto:support@wedding.com">Contact Support</a>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Important Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Payment Security</h6>
                            <ul class="small">
                                <li>All payments are processed securely through ToyyibPay</li>
                                <li>Your card information is not stored on our servers</li>
                                <li>You will receive email confirmations for all transactions</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Refund Policy</h6>
                            <ul class="small">
                                <li>Refund requests must be made at least 30 days before the event</li>
                                <li>Processing fees may apply for refunds</li>
                                <li>Contact our support team for refund requests</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-refresh page for pending payments
    <?php if ($payment_status === 'pending'): ?>
    setTimeout(function() {
        if (confirm('Would you like to refresh the page to check for payment updates?')) {
            location.reload();
        }
    }, 30000); // Refresh after 30 seconds
    <?php endif; ?>

    // Store payment result in session storage for analytics
    const paymentResult = {
        status: '<?php echo $payment_status; ?>',
        billcode: '<?php echo htmlspecialchars($billcode); ?>',
        timestamp: new Date().toISOString()
    };
    
    try {
        sessionStorage.setItem('last_payment_result', JSON.stringify(paymentResult));
    } catch (e) {
        // Ignore localStorage errors
    }
});
</script>

<?php include 'layouts/footer.php'; ?>