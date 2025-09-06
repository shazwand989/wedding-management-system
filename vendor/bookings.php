<?php
session_start();
require_once '../includes/config.php';

// Check if user is vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: ../login.php');
    exit();
}

// Define access constant for layout
define('VENDOR_ACCESS', true);

// Page variables
$page_header = 'My Bookings';
$page_description = 'Manage your wedding service bookings';

// Get vendor ID
$stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    header('Location: ../login.php');
    exit();
}

$vendor_id = $vendor['id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_booking_status':
                $booking_vendor_id = (int)$_POST['booking_vendor_id'];
                $status = $_POST['status'];
                
                $allowed_statuses = ['pending', 'confirmed', 'cancelled'];
                if (!in_array($status, $allowed_statuses)) {
                    throw new Exception('Invalid status');
                }
                
                // Verify this booking belongs to current vendor
                $stmt = $pdo->prepare("SELECT booking_id FROM booking_vendors WHERE id = ? AND vendor_id = ?");
                $stmt->execute([$booking_vendor_id, $vendor_id]);
                if (!$stmt->fetch()) {
                    throw new Exception('Unauthorized access to booking');
                }
                
                $stmt = $pdo->prepare("UPDATE booking_vendors SET status = ? WHERE id = ?");
                $stmt->execute([$status, $booking_vendor_id]);
                
                echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["bv.vendor_id = ?"];
$params = [$vendor_id];

if ($status_filter) {
    $where_conditions[] = "bv.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(b.event_date) = ?";
    $params[] = $date_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR b.venue_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get bookings for this vendor
$query = "
    SELECT bv.*, b.event_date, b.event_time, b.venue_name, b.guest_count, 
           b.booking_status, b.special_requests,
           u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM booking_vendors bv
    LEFT JOIN bookings b ON bv.booking_id = b.id
    LEFT JOIN users u ON b.customer_id = u.id
    $where_clause
    ORDER BY b.event_date DESC, bv.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN bv.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN bv.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN bv.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(bv.agreed_price) as total_earnings,
        SUM(CASE WHEN bv.status = 'confirmed' THEN bv.agreed_price ELSE 0 END) as confirmed_earnings
    FROM booking_vendors bv
    WHERE bv.vendor_id = ?
";
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$vendor_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Wedding Management System</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'layouts/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'layouts/sidebar.php'; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['total_bookings']); ?></h4>
                                    <p class="mb-0">Total Bookings</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-check fa-2x"></i>
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
                                    <h4><?php echo number_format($stats['pending_bookings']); ?></h4>
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
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['confirmed_bookings']); ?></h4>
                                    <p class="mb-0">Confirmed</p>
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
                                    <h4>RM <?php echo number_format($stats['confirmed_earnings'] ?: 0, 0); ?></h4>
                                    <p class="mb-0">Confirmed Earnings</p>
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
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer name or venue...">
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

            <!-- Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">My Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Event Date</th>
                                    <th>Customer</th>
                                    <th>Venue</th>
                                    <th>Guests</th>
                                    <th>Service</th>
                                    <th>Agreed Price</th>
                                    <th>Booking Status</th>
                                    <th>My Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($booking['event_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($booking['event_time'])); ?></small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['venue_name'] ?: 'Not specified'); ?></td>
                                            <td><?php echo number_format($booking['guest_count']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_type'] ?: 'General Service'); ?></td>
                                            <td>
                                                <?php if ($booking['agreed_price']): ?>
                                                    RM <?php echo number_format($booking['agreed_price'], 2); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['booking_status'] === 'confirmed' ? 'success' : 
                                                        ($booking['booking_status'] === 'pending' ? 'warning' : 
                                                        ($booking['booking_status'] === 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                                        ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewBooking(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="updateStatus(<?php echo $booking['id']; ?>, 'pending')">
                                                            <i class="fas fa-clock"></i>
                                                        </button>
                                                    <?php endif; ?>
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

        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetails">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script>
function updateStatus(bookingVendorId, status) {
    const action = status === 'confirmed' ? 'confirm' : (status === 'cancelled' ? 'cancel' : 'mark as pending');
    
    if (confirm(`Are you sure you want to ${action} this booking?`)) {
        fetch('bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_booking_status&booking_vendor_id=${bookingVendorId}&status=${status}`
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
            alert('Error updating booking status');
        });
    }
}

function viewBooking(bookingId) {
    // Load booking details via AJAX
    fetch(`../includes/ajax_handler.php?action=get_booking_details&id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('bookingDetails').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('bookingModal')).show();
            } else {
                alert('Error loading booking details');
            }
        })
        .catch(error => {
            alert('Error loading booking details');
        });
}
</script>

</body>
</html>
