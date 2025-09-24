<?php
define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Page configuration
$page_title = 'Admin Dashboard';
$page_header = 'Admin Dashboard';
$page_description = 'Welcome back, ' . htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin') . '!';

// Get dashboard statistics
try {
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $stmt->fetch()['total'];

    // Pending bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'pending'");
    $pending_bookings = $stmt->fetch()['total'];

    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE booking_status = 'completed'");
    $total_revenue = $stmt->fetch()['total'] ?? 0;

    // Total vendors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vendors WHERE status = 'active'");
    $total_vendors = $stmt->fetch()['total'];

    // Recent bookings
    $stmt = $pdo->query("
        SELECT b.*, u.full_name as customer_name, wp.name as package_name 
        FROM bookings b 
        LEFT JOIN users u ON b.customer_id = u.id 
        LEFT JOIN wedding_packages wp ON b.package_id = wp.id 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();

    // Pending vendor approvals
    $stmt = $pdo->query("
        SELECT v.*, u.full_name, u.email 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.status = 'pending' 
        ORDER BY v.created_at DESC
    ");
    $pending_vendors = $stmt->fetchAll();
} catch (PDOException $e) {
    $total_bookings = $pending_bookings = $total_revenue = $total_vendors = 0;
    $recent_bookings = $pending_vendors = [];
}

// Include layout header
include 'layouts/header.php';
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-warning">
            <div class="inner">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <a href="bookings.php" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-info">
            <div class="inner">
                <h3><?php echo $pending_bookings; ?></h3>
                <p>Pending Bookings</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="bookings.php?status=pending" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-success">
            <div class="inner">
                <h3>RM <?php echo number_format($total_revenue, 0); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <a href="reports.php" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-danger">
            <div class="inner">
                <h3><?php echo $total_vendors; ?></h3>
                <p>Active Vendors</p>
            </div>
            <div class="icon">
                <i class="fas fa-store"></i>
            </div>
            <a href="vendors.php" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Visitor Analytics Widget -->
<?php require_once '../includes/visitor_analytics_widget.php'; ?>

<!-- Quick Actions -->
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bolt"></i> Quick Actions
        </h3>
        <div class="card-tools">
            <span class="badge badge-warning">Admin Tools</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <a href="bookings.php?action=new" class="btn btn-warning btn-block btn-lg">
                    <i class="fas fa-plus"></i><br>New Booking
                </a>
            </div>
            <div class="col-md-3">
                <a href="vendors.php?action=pending" class="btn btn-info btn-block btn-lg">
                    <i class="fas fa-user-check"></i><br>Approve Vendors
                </a>
            </div>
            <div class="col-md-3">
                <a href="packages.php?action=new" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-box-open"></i><br>Add Package
                </a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="btn btn-secondary btn-block btn-lg">
                    <i class="fas fa-download"></i><br>Export Report
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Bookings -->
    <div class="col-md-8">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check"></i> Recent Bookings
                </h3>
                <div class="card-tools">
                    <a href="bookings.php" class="btn btn-warning btn-xs">View All</a>
                </div>
            </div>
            <?php if (empty($recent_bookings)): ?>
                <div class="card-body text-center">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No bookings found.</p>
                </div>
            <?php else: ?>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Event Date</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-friends mr-1"></i>
                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('M j, Y', strtotime($booking['event_date'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['package_name'] ?? 'Custom'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'cancelled' ? 'danger' : 'info'));
                                                                    ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong>RM <?php echo number_format($booking['total_amount'], 2); ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Vendor Approvals -->
    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-clock"></i> Pending Approvals
                </h3>
                <div class="card-tools">
                    <a href="vendors.php?status=pending" class="btn btn-info btn-xs">View All</a>
                </div>
            </div>
            <?php if (empty($pending_vendors)): ?>
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">No pending approvals.</p>
                </div>
            <?php else: ?>
                <div class="card-body p-0">
                    <?php foreach ($pending_vendors as $vendor): ?>
                        <div class="d-flex p-3 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($vendor['business_name']); ?></h6>
                                <p class="mb-1 text-sm">
                                    <?php echo htmlspecialchars($vendor['full_name']); ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-tags mr-1"></i>
                                    <?php echo ucfirst($vendor['service_type']); ?>
                                </small>
                            </div>
                            <div class="btn-group-vertical">
                                <button onclick="updateVendorStatus(<?php echo $vendor['id']; ?>, 'active')"
                                    class="btn btn-success btn-xs">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="updateVendorStatus(<?php echo $vendor['id']; ?>, 'inactive')"
                                    class="btn btn-danger btn-xs">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Calendar Section -->
<div class="card card-success card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-alt"></i> Upcoming Events
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="calendar-container">
            <!-- Calendar will be loaded here via JavaScript -->
            <div class="text-center p-4">
                <div class="spinner-border text-warning" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading upcoming events...</p>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
<script>
    function updateVendorStatus(vendorId, status) {
        const action = status === 'active' ? 'approve' : 'reject';

        Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} Vendor?`,
            text: `Are you sure you want to ${action} this vendor?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: status === 'active' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${action} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'includes/ajax_handler.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_vendor_status',
                        vendor_id: vendorId,
                        status: status
                    },
                    success: function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'An error occurred.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating vendor status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }

    // Load upcoming events calendar
    $(document).ready(function() {
        loadUpcomingEvents();
    });

    function loadUpcomingEvents() {
        $.ajax({
            url: '../includes/get_upcoming_events.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                const container = $('#calendar-container');
                if (data.success && data.events.length > 0) {
                    let html = '<div class="row">';
                    data.events.forEach(event => {
                        const eventDate = new Date(event.event_date);
                        const today = new Date();
                        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));

                        html += `
                        <div class="col-md-4 mb-3">
                            <div class="card card-widget widget-user-2 shadow-sm">
                                <div class="widget-user-header bg-gradient-warning">
                                    <div class="widget-user-image">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                    <h3 class="widget-user-username">${event.customer_name}</h3>
                                    <h5 class="widget-user-desc">Wedding Event</h5>
                                </div>
                                <div class="card-footer p-0">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <span class="nav-link">
                                                <i class="fas fa-calendar text-warning"></i> ${formatDate(event.event_date)}
                                            </span>
                                        </li>
                                        <li class="nav-item">
                                            <span class="nav-link">
                                                <i class="fas fa-clock text-info"></i> ${formatTime(event.event_time)}
                                            </span>
                                        </li>
                                        <li class="nav-item">
                                            <span class="nav-link">
                                                <i class="fas fa-map-marker-alt text-danger"></i> ${event.venue_name || 'TBD'}
                                            </span>
                                        </li>
                                        <li class="nav-item">
                                            <div class="nav-link d-flex justify-content-between">
                                                <span class="badge badge-info">${daysUntil} days away</span>
                                                <span class="badge badge-${event.booking_status === 'confirmed' ? 'success' : 'warning'}">${event.booking_status}</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                    });
                    html += '</div>';
                    container.html(html);
                } else {
                    container.html(`
                    <div class="text-center p-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No upcoming events.</p>
                    </div>
                `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading events:', error);
                $('#calendar-container').html(`
                <div class="text-center p-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p class="text-muted">Error loading events.</p>
                </div>
            `);
            }
        });
    }

    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatTime(timeString) {
        if (!timeString) return 'TBD';
        const time = new Date('2000-01-01 ' + timeString);
        return time.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }
</script>