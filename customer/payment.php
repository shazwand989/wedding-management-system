<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

$customer_id = $_SESSION['user_id'];
$page_title = 'Make Payment';

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    $_SESSION['error_message'] = 'Invalid booking ID';
    redirectTo('bookings.php');
}

// Verify booking ownership and get booking details
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name, u.email, u.phone, wp.name as package_name
    FROM bookings b
    LEFT JOIN users u ON b.customer_id = u.id
    LEFT JOIN wedding_packages wp ON b.package_id = wp.id
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error_message'] = 'Booking not found or access denied';
    redirectTo('bookings.php');
}

// Calculate remaining amount
$remaining_amount = $booking['total_amount'] - $booking['paid_amount'];

// Also check payment_status field for additional validation
if ($remaining_amount <= 0 || $booking['payment_status'] === 'paid') {
    $_SESSION['success_message'] = 'This booking is already fully paid';
    redirectTo('bookings.php');
}

if ($booking['booking_status'] === 'cancelled') {
    $_SESSION['error_message'] = 'Cannot make payment for cancelled booking';
    redirectTo('bookings.php');
}

// Handle payment form submission
if ($_POST && isset($_POST['pay_now'])) {
    $payment_amount = (float)$_POST['payment_amount'];
    $payment_type = $_POST['payment_type'] ?? 'full'; // 'full' or 'partial'
    
    // Validate payment amount
    if ($payment_type === 'full') {
        $payment_amount = $remaining_amount;
    } elseif ($payment_amount <= 0 || $payment_amount > $remaining_amount) {
        $error_message = 'Invalid payment amount. Amount must be between RM 1 and RM ' . number_format($remaining_amount, 2);
    }
    
    if (!isset($error_message)) {
        try {
            $toyyibpay = new ToyyibPay();
            
            // Validate payment
            $validation = $toyyibpay->validatePayment($booking_id, $payment_amount, $pdo);
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            // Create ToyyibPay bill
            $billParams = [
                'billName' => 'Wedding Booking Payment #' . $booking_id,
                'billDescription' => 'Payment for ' . ($booking['package_name'] ?: 'Wedding Package') . ' - Event Date: ' . date('M j, Y', strtotime($booking['event_date'])),
                'billAmount' => $payment_amount,
                'billExternalReferenceNo' => $booking_id,
                'billTo' => $booking['full_name'],
                'billEmail' => $booking['email'],
                'billPhone' => $booking['phone'] ?: '60123456789'
            ];
            
            $result = $toyyibpay->createBill($billParams, $pdo);
            
            if ($result['success']) {
                // Store payment attempt in session for tracking
                $_SESSION['payment_attempt'] = [
                    'booking_id' => $booking_id,
                    'amount' => $payment_amount,
                    'bill_code' => $result['billCode'],
                    'created_at' => time()
                ];
                
                // Redirect to ToyyibPay payment page
                redirectTo($result['paymentUrl']);
            } else {
                throw new Exception('Failed to create payment bill: ' . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Payment creation error: " . $e->getMessage());
        }
    }
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Booking Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-receipt"></i> Payment for Booking #<?php echo $booking['id']; ?></h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Booking Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Package:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['package_name'] ?: 'Custom Package'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Event Date:</strong></td>
                                    <td><?php echo date('F j, Y', strtotime($booking['event_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Event Time:</strong></td>
                                    <td><?php echo date('g:i A', strtotime($booking['event_time'])); ?></td>
                                </tr>
                                <?php if ($booking['venue_name']): ?>
                                <tr>
                                    <td><strong>Venue:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['venue_name']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Guests:</strong></td>
                                    <td><?php echo $booking['guest_count']; ?> people</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Summary</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="text-right"><strong>RM <?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Paid Amount:</td>
                                    <td class="text-right">RM <?php echo number_format($booking['paid_amount'], 2); ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong class="text-danger">Outstanding Balance:</strong></td>
                                    <td class="text-right"><strong class="text-danger">RM <?php echo number_format($remaining_amount, 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Options</h5>
                </div>
                <form method="POST" id="paymentForm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Type</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="payment_full" value="full" checked>
                                        <label class="form-check-label" for="payment_full">
                                            <strong>Pay Full Amount</strong> - RM <?php echo number_format($remaining_amount, 2); ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="payment_partial" value="partial">
                                        <label class="form-check-label" for="payment_partial">
                                            <strong>Pay Partial Amount</strong>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group" id="partial_amount_group" style="display: none;">
                                    <label for="payment_amount">Payment Amount (RM)</label>
                                    <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                           min="1" max="<?php echo $remaining_amount; ?>" step="0.01" 
                                           placeholder="Enter amount">
                                    <small class="form-text text-muted">
                                        Amount must be between RM 1.00 and RM <?php echo number_format($remaining_amount, 2); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-shield-alt text-success"></i> Secure Payment with ToyyibPay</h6>
                                        <p class="small mb-2">Your payment will be processed securely through ToyyibPay. Accepted payment methods:</p>
                                        <ul class="small mb-0">
                                            <li>Online Banking (FPX)</li>
                                            <li>Credit/Debit Cards (Visa, MasterCard)</li>
                                            <li>E-wallets (Boost, GrabPay, TouchnGo)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Important:</strong> After clicking "Proceed to Payment", you will be redirected to ToyyibPay's secure payment page. 
                            Please complete your payment there and wait to be redirected back to our website.
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="bookings.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Bookings
                            </a>
                            <button type="submit" name="pay_now" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Proceed to Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Payment History -->
            <?php if ($booking['paid_amount'] > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT * FROM payments 
                        WHERE booking_id = ? AND status = 'completed'
                        ORDER BY payment_date DESC, created_at DESC
                    ");
                    $stmt->execute([$booking_id]);
                    $payments = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($payments)): ?>
                        <p class="text-muted">No payment history available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Transaction ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td>
                                            <?php if ($payment['transaction_id']): ?>
                                                <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Completed</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle partial payment amount field
    $('input[name="payment_type"]').change(function() {
        if ($(this).val() === 'partial') {
            $('#partial_amount_group').show();
            $('#payment_amount').prop('required', true);
        } else {
            $('#partial_amount_group').hide();
            $('#payment_amount').prop('required', false).val('');
        }
    });

    // Form validation
    $('#paymentForm').on('submit', function(e) {
        const paymentType = $('input[name="payment_type"]:checked').val();
        
        if (paymentType === 'partial') {
            const amount = parseFloat($('#payment_amount').val());
            const maxAmount = <?php echo $remaining_amount; ?>;
            
            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid payment amount.');
                return false;
            }
            
            if (amount > maxAmount) {
                e.preventDefault();
                alert('Payment amount cannot exceed the outstanding balance of RM ' + maxAmount.toFixed(2));
                return false;
            }
        }
        
        // Show loading state
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    });
});
</script>

<?php include 'layouts/footer.php'; ?>