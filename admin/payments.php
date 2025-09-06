<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Define access constant for layout
define('ADMIN_ACCESS', true);

// Page variables
$page_header = 'Payment Management';
$page_description = 'Track and manage all payments';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_payment':
                $booking_id = (int)$_POST['booking_id'];
                $amount = (float)$_POST['amount'];
                $payment_method = $_POST['payment_method'];
                $transaction_id = trim($_POST['transaction_id']);
                $payment_date = $_POST['payment_date'];
                $notes = trim($_POST['notes']);

                if ($amount <= 0) {
                    throw new Exception('Payment amount must be greater than 0');
                }

                // Check if booking exists
                $stmt = $pdo->prepare("SELECT total_amount, paid_amount FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$booking) {
                    throw new Exception('Booking not found');
                }

                $new_paid_amount = $booking['paid_amount'] + $amount;
                if ($new_paid_amount > $booking['total_amount']) {
                    throw new Exception('Payment amount exceeds remaining balance');
                }

                // Insert payment
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_date, status, notes) VALUES (?, ?, ?, ?, ?, 'completed', ?)");
                $stmt->execute([$booking_id, $amount, $payment_method, $transaction_id, $payment_date, $notes]);

                // Update booking paid amount and payment status
                $payment_status = 'partial';
                if ($new_paid_amount >= $booking['total_amount']) {
                    $payment_status = 'paid';
                } elseif ($new_paid_amount == 0) {
                    $payment_status = 'pending';
                }

                $stmt = $pdo->prepare("UPDATE bookings SET paid_amount = ?, payment_status = ? WHERE id = ?");
                $stmt->execute([$new_paid_amount, $payment_status, $booking_id]);

                echo json_encode(['success' => true, 'message' => 'Payment added successfully']);
                exit();

            case 'update_payment_status':
                $payment_id = (int)$_POST['payment_id'];
                $status = $_POST['status'];

                $allowed_statuses = ['pending', 'completed', 'failed', 'refunded'];
                if (!in_array($status, $allowed_statuses)) {
                    throw new Exception('Invalid status');
                }

                // Get payment details
                $stmt = $pdo->prepare("SELECT booking_id, amount, status as current_status FROM payments WHERE id = ?");
                $stmt->execute([$payment_id]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$payment) {
                    throw new Exception('Payment not found');
                }

                // Update payment status
                $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
                $stmt->execute([$status, $payment_id]);

                // Recalculate booking payment totals
                $stmt = $pdo->prepare("
                    UPDATE bookings b 
                    SET paid_amount = (
                        SELECT COALESCE(SUM(amount), 0) 
                        FROM payments 
                        WHERE booking_id = b.id AND status = 'completed'
                    )
                    WHERE id = ?
                ");
                $stmt->execute([$payment['booking_id']]);

                // Update booking payment status
                $stmt = $pdo->prepare("SELECT total_amount, paid_amount FROM bookings WHERE id = ?");
                $stmt->execute([$payment['booking_id']]);
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);

                $payment_status = 'pending';
                if ($booking['paid_amount'] >= $booking['total_amount']) {
                    $payment_status = 'paid';
                } elseif ($booking['paid_amount'] > 0) {
                    $payment_status = 'partial';
                }

                $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
                $stmt->execute([$payment_status, $payment['booking_id']]);

                echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($method_filter) {
    $where_conditions[] = "p.payment_method = ?";
    $params[] = $method_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(p.payment_date) = ?";
    $params[] = $date_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR p.transaction_id LIKE ? OR b.id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get payments with booking and customer details
$query = "
    SELECT p.*, b.id as booking_id, b.total_amount, b.event_date,
           u.full_name as customer_name, u.email as customer_email
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN users u ON b.customer_id = u.id
    $where_clause
    ORDER BY p.payment_date DESC, p.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_payments,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_completed,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as total_pending,
        COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) as total_failed,
        COALESCE(SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END), 0) as total_refunded,
        COUNT(CASE WHEN DATE(payment_date) = CURDATE() THEN 1 END) as today_payments,
        COALESCE(SUM(CASE WHEN DATE(payment_date) = CURDATE() AND status = 'completed' THEN amount ELSE 0 END), 0) as today_revenue
    FROM payments
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get bookings for dropdown (active bookings only)
$bookings_query = "
    SELECT b.id, u.full_name, b.total_amount, b.paid_amount, b.event_date
    FROM bookings b
    LEFT JOIN users u ON b.customer_id = u.id
    WHERE b.booking_status IN ('pending', 'confirmed') AND b.paid_amount < b.total_amount
    ORDER BY b.event_date ASC
";
$available_bookings = $pdo->query($bookings_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($stats['total_completed'], 2); ?></h4>
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
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($stats['total_pending'], 2); ?></h4>
                        <p class="mb-0">Pending</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4><?php echo number_format($stats['today_payments']); ?></h4>
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
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($stats['today_revenue'], 2); ?></h4>
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="method" class="form-label">Method</label>
                <select class="form-select" id="method" name="method">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo $method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="card" <?php echo $method_filter === 'card' ? 'selected' : ''; ?>>Card</option>
                    <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="online" <?php echo $method_filter === 'online' ? 'selected' : ''; ?>>Online</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date" class="form-label">Payment Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer name, transaction ID, or booking ID...">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Payments</h5>
        <div>
            <button class="btn btn-success btn-sm" onclick="exportPayments()">
                <i class="fas fa-download"></i> Export
            </button>
            <button class="btn btn-primary btn-sm" onclick="addPayment()">
                <i class="fas fa-plus"></i> Add Payment
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No payments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['id']; ?></td>
                                <td>
                                    <a href="bookings.php?booking_id=<?php echo $payment['booking_id']; ?>" class="text-decoration-none">
                                        #<?php echo $payment['booking_id']; ?>
                                    </a><br>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($payment['event_date'])); ?></small>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($payment['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <strong>RM <?php echo number_format($payment['amount'], 2); ?></strong><br>
                                    <small class="text-muted">of RM <?php echo number_format($payment['total_amount'], 2); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['transaction_id']): ?>
                                        <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : ($payment['status'] === 'failed' ? 'danger' : 'info'));
                                                            ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'pending')">Mark Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'completed')">Mark Completed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'failed')">Mark Failed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'refunded')">Mark Refunded</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="booking_id" class="form-label">Booking *</label>
                        <select class="form-select" id="booking_id" name="booking_id" required>
                            <option value="">Select a booking...</option>
                            <?php foreach ($available_bookings as $booking): ?>
                                <option value="<?php echo $booking['id']; ?>"
                                    data-total="<?php echo $booking['total_amount']; ?>"
                                    data-paid="<?php echo $booking['paid_amount']; ?>">
                                    #<?php echo $booking['id']; ?> - <?php echo htmlspecialchars($booking['full_name']); ?>
                                    (<?php echo date('M j, Y', strtotime($booking['event_date'])); ?>)
                                    - Balance: RM <?php echo number_format($booking['total_amount'] - $booking['paid_amount'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (RM) *</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" required>
                        <div class="form-text" id="balanceInfo"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select method...</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="online">Online Payment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date *</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="transaction_id" class="form-label">Transaction ID</label>
                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Optional transaction reference">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional payment notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetails">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script>
    function addPayment() {
        document.getElementById('paymentForm').reset();
        document.getElementById('payment_date').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('balanceInfo').textContent = '';
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }

    function updatePaymentStatus(paymentId, status) {
        Swal.fire({
            title: 'Update Payment Status?',
            text: `Are you sure you want to mark this payment as "${status}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait while we update the payment status.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('payments.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=update_payment_status&payment_id=${paymentId}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Updated!',
                                text: 'Payment status has been updated successfully.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'An error occurred while updating the payment status.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating the payment status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        });
    }

    function viewPayment(paymentId) {
        // Load payment details via AJAX
        fetch(`../includes/ajax_handler.php?action=get_payment_details&id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('paymentDetails').innerHTML = data.html;
                    new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
                } else {
                    alert('Error loading payment details');
                }
            })
            .catch(error => {
                alert('Error loading payment details');
            });
    }

    function exportPayments() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = 'payments.php?' + params.toString();
    }

    // Handle booking selection
    document.getElementById('booking_id').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            const total = parseFloat(option.dataset.total);
            const paid = parseFloat(option.dataset.paid);
            const balance = total - paid;

            document.getElementById('balanceInfo').textContent = `Remaining balance: RM ${balance.toFixed(2)}`;
            document.getElementById('amount').max = balance;
            document.getElementById('amount').value = balance;
        } else {
            document.getElementById('balanceInfo').textContent = '';
            document.getElementById('amount').removeAttribute('max');
            document.getElementById('amount').value = '';
        }
    });

    // Handle form submission
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'add_payment');

        fetch('payments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error adding payment');
            });
    });
</script>