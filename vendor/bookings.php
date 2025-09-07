<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'My Bookings';
$page_header = 'Booking Management';
$page_description = 'Manage your booking requests and confirmed events';

$vendor_user_id = $_SESSION['user_id'];

// Get vendor information
try {
    $stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
    $stmt->execute([$vendor_user_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        redirectTo('../login.php');
    }

    $vendor_id = $vendor['id'];

    // Handle booking status updates
    if ($_POST && isset($_POST['action']) && isset($_POST['booking_vendor_id'])) {
        $booking_vendor_id = $_POST['booking_vendor_id'];
        $action = $_POST['action'];
        $agreed_price = $_POST['agreed_price'] ?? null;
        $notes = $_POST['notes'] ?? '';

        if ($action === 'confirm' && $agreed_price) {
            $stmt = $pdo->prepare("UPDATE booking_vendors SET status = 'confirmed', agreed_price = ?, notes = ? WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$agreed_price, $notes, $booking_vendor_id, $vendor_id]);
            
            // Update vendor earnings
            $stmt = $pdo->prepare("UPDATE vendors SET rating = rating WHERE id = ?");
            $stmt->execute([$vendor_id]);
            
            $success_message = "Booking confirmed successfully!";
        } elseif ($action === 'decline') {
            $stmt = $pdo->prepare("UPDATE booking_vendors SET status = 'cancelled', notes = ? WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$notes, $booking_vendor_id, $vendor_id]);
            $success_message = "Booking declined.";
        }
    }

    // Get filter parameters
    $status_filter = $_GET['status'] ?? 'all';
    $date_filter = $_GET['date'] ?? 'all';

    // Build query based on filters
    $where_conditions = ["bv.vendor_id = ?"];
    $params = [$vendor_id];

    if ($status_filter !== 'all') {
        $where_conditions[] = "bv.status = ?";
        $params[] = $status_filter;
    }

    if ($date_filter === 'upcoming') {
        $where_conditions[] = "b.event_date >= CURDATE()";
    } elseif ($date_filter === 'past') {
        $where_conditions[] = "b.event_date < CURDATE()";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get vendor's bookings
    $stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, b.event_time, b.venue_name, b.venue_address, 
               b.guest_count, b.special_requests, b.booking_status, b.total_amount,
               u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
               p.name as package_name, p.price as package_price
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        LEFT JOIN wedding_packages p ON b.package_id = p.id
        WHERE $where_clause
        ORDER BY b.event_date DESC, bv.created_at DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN bv.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN bv.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN bv.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM booking_vendors bv
        WHERE bv.vendor_id = ?
    ");
    $stats_stmt->execute([$vendor_id]);
    $stats = $stats_stmt->fetch();
    
    // Ensure stats have default values of 0 instead of null
    $stats = [
        'total' => (int)($stats['total'] ?? 0),
        'pending' => (int)($stats['pending'] ?? 0),
        'confirmed' => (int)($stats['confirmed'] ?? 0),
        'cancelled' => (int)($stats['cancelled'] ?? 0)
    ];

} catch (PDOException $e) {
    $error_message = "Error loading bookings: " . $e->getMessage();
    $bookings = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0];
}

// Include layout header
include 'layouts/header.php';
?>

<div class="container-fluid">

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x mb-3"></i>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['confirmed']; ?></h3>
                    <p>Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-danger">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['cancelled']; ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Bookings</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date Range:</label>
                            <select name="date" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Dates</option>
                                <option value="upcoming" <?php echo $date_filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                                <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Past Events</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <a href="bookings.php" class="btn btn-secondary">Reset Filters</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Bookings</h3>
        </div>
        <div class="card-body">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>No Bookings Found</h4>
                    <p class="text-muted">No bookings match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>Event Date</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Venue</th>
                                <th>Guests</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($booking['event_time'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['customer_email']); ?><br>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone'] ?: 'Not provided'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($booking['package_name']): ?>
                                            <strong><?php echo htmlspecialchars($booking['package_name']); ?></strong><br>
                                            <small class="text-primary">RM <?php echo number_format($booking['package_price'], 2); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Custom Package</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['venue_name']): ?>
                                            <strong><?php echo htmlspecialchars($booking['venue_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['venue_address']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">TBD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-users"></i> <?php echo $booking['guest_count']; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['agreed_price']): ?>
                                            <strong class="text-success">RM <?php echo number_format($booking['agreed_price'], 2); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                                                        ($booking['status'] === 'pending' ? 'warning' : 'danger');
                                                                    ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm" onclick="confirmBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="declineBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-times"></i> Decline
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Booking Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="bookingModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Confirm Booking Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h4 class="modal-title">Confirm Booking</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="booking_vendor_id" id="confirmBookingId">
                    
                    <div class="form-group">
                        <label>Agreed Price (RM):</label>
                        <input type="number" name="agreed_price" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes (Optional):</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes or terms..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Decline Booking Modal -->
<div class="modal fade" id="declineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h4 class="modal-title">Decline Booking</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="decline">
                    <input type="hidden" name="booking_vendor_id" id="declineBookingId">
                    
                    <p>Are you sure you want to decline this booking request?</p>
                    
                    <div class="form-group">
                        <label>Reason for declining (Optional):</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Please provide a reason for declining..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Decline Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewBooking(bookingVendorId) {
    // Load booking details via AJAX
    $.get('../includes/ajax_handler.php', {
        action: 'get_booking_details',
        booking_vendor_id: bookingVendorId
    }, function(response) {
        $('#bookingModalBody').html(response);
        $('#bookingModal').modal('show');
    });
}

function confirmBooking(bookingVendorId) {
    $('#confirmBookingId').val(bookingVendorId);
    $('#confirmModal').modal('show');
}

function declineBooking(bookingVendorId) {
    $('#declineBookingId').val(bookingVendorId);
    $('#declineModal').modal('show');
}
</script>

<?php include 'layouts/footer.php'; ?>
