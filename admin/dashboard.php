<?php
define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Admin Dashboard';
$page_header = 'Admin Dashboard';
$page_description = 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!';

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
include 'layouts/sidebar.php';
?>

<!-- Statistics Cards -->
<div class="dashboard-stats">
    <div class="stat-card">
        <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
        <span class="stat-number"><?php echo $total_bookings; ?></span>
        <span class="stat-label">Total Bookings</span>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
        <span class="stat-number"><?php echo $pending_bookings; ?></span>
        <span class="stat-label">Pending Bookings</span>
    </div>
    <div class="stat-card">
        <i class="fas fa-money-bill-wave" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
        <span class="stat-number">RM <?php echo number_format($total_revenue, 0); ?></span>
        <span class="stat-label">Total Revenue</span>
    </div>
    <div class="stat-card">
        <i class="fas fa-store" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
        <span class="stat-number"><?php echo $total_vendors; ?></span>
        <span class="stat-label">Active Vendors</span>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <a href="bookings.php?action=new" class="btn">
            <i class="fas fa-plus"></i> New Booking
        </a>
        <a href="vendors.php?action=pending" class="btn btn-warning">
            <i class="fas fa-user-check"></i> Approve Vendors
        </a>
        <a href="packages.php?action=new" class="btn btn-secondary">
            <i class="fas fa-box-open"></i> Add Package
        </a>
        <a href="reports.php" class="btn" style="background-color: var(--info-color);">
            <i class="fas fa-download"></i> Export Report
        </a>
    </div>
</div>

<div class="grid grid-2">
    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Bookings</h3>
            <a href="bookings.php" style="color: var(--primary-color);">View All →</a>
        </div>
        <?php if (empty($recent_bookings)): ?>
            <p style="text-align: center; color: #666; padding: 2rem;">No bookings found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table">
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
                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($booking['package_name'] ?? 'Custom'); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'cancelled' ? 'danger' : 'info'));
                                                                ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Vendor Approvals -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pending Vendor Approvals</h3>
            <a href="vendors.php?status=pending" style="color: var(--primary-color);">View All →</a>
        </div>
        <?php if (empty($pending_vendors)): ?>
            <p style="text-align: center; color: #666; padding: 2rem;">No pending approvals.</p>
        <?php else: ?>
            <?php foreach ($pending_vendors as $vendor): ?>
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($vendor['business_name']); ?></h4>
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <?php echo htmlspecialchars($vendor['full_name']); ?> •
                            <?php echo ucfirst($vendor['service_type']); ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="updateVendorStatus(<?php echo $vendor['id']; ?>, 'active')"
                            class="btn btn-success" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="updateVendorStatus(<?php echo $vendor['id']; ?>, 'inactive')"
                            class="btn btn-danger" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Calendar Section -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Upcoming Events</h3>
    </div>
    <div id="calendar-container">
        <!-- Calendar will be loaded here via JavaScript -->
        <p style="text-align: center; color: #666; padding: 2rem;">
            <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
            Event calendar loading...
        </p>
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
                const formData = new FormData();
                formData.append('action', 'update_vendor_status');
                formData.append('vendor_id', vendorId);
                formData.append('status', status);

                fetch('includes/ajax_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
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
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating vendor status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        });
    }

    // Load upcoming events calendar
    document.addEventListener('DOMContentLoaded', function() {
        loadUpcomingEvents();
    });

    function loadUpcomingEvents() {
        fetch('includes/get_upcoming_events.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('calendar-container');
                if (data.success && data.events.length > 0) {
                    let html = '<div class=\"grid grid-3\">';
                    data.events.forEach(event => {
                        const eventDate = new Date(event.event_date);
                        const today = new Date();
                        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));

                        html += `
                                <div class=\"card\" style=\"margin: 0;\">
                                    <h4 style=\"margin-bottom: 1rem; color: var(--primary-color);\">\${event.customer_name}</h4>
                                    <p style=\"margin-bottom: 0.5rem;\"><i class=\"fas fa-calendar\"></i> \${WeddingManagement.formatDate(event.event_date)}</p>
                                    <p style=\"margin-bottom: 0.5rem;\"><i class=\"fas fa-clock\"></i> \${WeddingManagement.formatTime(event.event_time)}</p>
                                    <p style=\"margin-bottom: 1rem;\"><i class=\"fas fa-map-marker-alt\"></i> \${event.venue_name || 'TBD'}</p>
                                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                                        <span class=\"badge badge-info\">\${daysUntil} days</span>
                                        <span class=\"badge badge-\${event.booking_status === 'confirmed' ? 'success' : 'warning'}\">\${event.booking_status}</span>
                                    </div>
                                </div>
                            `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style=\"text-align: center; color: #666; padding: 2rem;\">No upcoming events.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading events:', error);
                document.getElementById('calendar-container').innerHTML = '<p style=\"text-align: center; color: #666; padding: 2rem;\">Error loading events.</p>';
            });
    }
</script>