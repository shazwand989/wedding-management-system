<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'My Bookings';
$breadcrumbs = [
    ['title' => 'My Bookings']
];

$customer_id = $_SESSION['user_id'];

// Handle booking actions
if ($_POST && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    
    // Verify booking ownership
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND customer_id = ?");
    $stmt->execute([$booking_id, $customer_id]);
    if ($stmt->fetch()) {
        if ($action === 'cancel') {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);
            $success_message = "Booking cancelled successfully.";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';

// Build query based on filters
$where_conditions = ["b.customer_id = ?"];
$params = [$customer_id];

if ($status_filter !== 'all') {
    $where_conditions[] = "b.booking_status = ?";
    $params[] = $status_filter;
}

if ($date_filter === 'upcoming') {
    $where_conditions[] = "b.event_date >= CURDATE()";
} elseif ($date_filter === 'past') {
    $where_conditions[] = "b.event_date < CURDATE()";
}

$where_clause = implode(' AND ', $where_conditions);

// Get customer's bookings
try {
    $stmt = $pdo->prepare("
        SELECT b.*, wp.name as package_name, wp.price as package_price
        FROM bookings b
        LEFT JOIN wedding_packages wp ON b.package_id = wp.id
        WHERE $where_clause
        ORDER BY b.event_date DESC, b.created_at DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN b.booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings b
        WHERE b.customer_id = ?
    ");
    $stats_stmt->execute([$customer_id]);
    $stats = $stats_stmt->fetch();
    
    // Ensure stats have default values
    $stats = [
        'total' => (int)($stats['total'] ?? 0),
        'pending' => (int)($stats['pending'] ?? 0),
        'confirmed' => (int)($stats['confirmed'] ?? 0),
        'completed' => (int)($stats['completed'] ?? 0),
        'cancelled' => (int)($stats['cancelled'] ?? 0)
    ];

} catch (PDOException $e) {
    $error_message = "Error loading bookings: " . $e->getMessage();
    $bookings = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
}

include 'layouts/header.php';
?>

<div class="container-fluid">

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
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
                    <p>Pending</p>
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
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x mb-3"></i>
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title">My Bookings</h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="new-booking.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Filter by Status:</label>
                    <select class="form-control" onchange="filterBookings()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Filter by Date:</label>
                    <select class="form-control" onchange="filterBookings()">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Dates</option>
                        <option value="upcoming" <?php echo $date_filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                        <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Past Events</option>
                    </select>
                </div>
            </div>

            <!-- Bookings List -->
            <?php if (empty($bookings)): ?>
                <div class="text-center p-5">
                    <i class="fas fa-calendar-plus fa-4x text-primary mb-4"></i>
                    <h4>No Bookings Found</h4>
                    <p class="text-muted">You haven't made any bookings yet or no bookings match your filters.</p>
                    <a href="new-booking.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Booking
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <?php echo htmlspecialchars($booking['package_name'] ?: 'Custom Package'); ?>
                                        </h5>
                                        <span class="badge badge-<?php
                                            echo $booking['booking_status'] === 'confirmed' ? 'success' : 
                                                ($booking['booking_status'] === 'pending' ? 'warning' : 
                                                ($booking['booking_status'] === 'completed' ? 'info' : 'danger'));
                                        ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <i class="fas fa-calendar text-primary"></i>
                                        <strong>Event Date:</strong><br>
                                        <?php echo date('F j, Y', strtotime($booking['event_date'])); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-clock text-primary"></i>
                                        <strong>Time:</strong><br>
                                        <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                    </div>
                                    
                                    <?php if ($booking['venue_name']): ?>
                                        <div class="mb-3">
                                            <i class="fas fa-map-marker-alt text-primary"></i>
                                            <strong>Venue:</strong><br>
                                            <?php echo htmlspecialchars($booking['venue_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-users text-primary"></i>
                                        <strong>Guests:</strong> <?php echo $booking['guest_count']; ?> people
                                    </div>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                        <strong>Total Amount:</strong><br>
                                        <span class="h5 text-success">RM <?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-credit-card text-info"></i>
                                        <strong>Paid:</strong> RM <?php echo number_format($booking['paid_amount'], 2); ?>
                                        <?php if ($booking['total_amount'] > $booking['paid_amount']): ?>
                                            <br><small class="text-warning">
                                                Balance: RM <?php echo number_format($booking['total_amount'] - $booking['paid_amount'], 2); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-primary btn-sm" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <?php if ($booking['booking_status'] === 'pending'): ?>
                                            <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($booking['total_amount'] > $booking['paid_amount'] && $booking['booking_status'] !== 'cancelled'): ?>
                                            <a href="payments.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-credit-card"></i> Pay
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterBookings() {
    const status = document.querySelector('select[onchange="filterBookings()"]').value;
    const date = document.querySelectorAll('select[onchange="filterBookings()"]')[1].value;
    
    let url = 'bookings.php?';
    if (status !== 'all') url += 'status=' + status + '&';
    if (date !== 'all') url += 'date=' + date + '&';
    
    window.location.href = url;
}

function viewBookingDetails(bookingId) {
    $('#bookingDetailsModal').modal('show');
    
    $.ajax({
        url: '../includes/ajax_handler.php',
        method: 'POST',
        data: {
            action: 'get_booking_details',
            booking_id: bookingId
        },
        success: function(response) {
            $('#bookingDetailsContent').html(response);
        },
        error: function() {
            $('#bookingDetailsContent').html('<div class="alert alert-danger">Error loading booking details.</div>');
        }
    });
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="booking_id" value="${bookingId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'layouts/footer.php'; ?>
