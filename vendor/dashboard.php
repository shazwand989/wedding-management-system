<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Vendor Dashboard';
$page_header = 'Vendor Dashboard';
$page_description = 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!';

$vendor_user_id = $_SESSION['user_id'];

// Get vendor information
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.full_name, u.email, u.phone, u.address 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.user_id = ?
    ");
    $stmt->execute([$vendor_user_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        redirectTo('../login.php');
    }

    $vendor_id = $vendor['id'];

    // Get vendor's bookings
    $stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, b.event_time, b.venue_name, b.booking_status, 
               u.full_name as customer_name, u.phone as customer_phone
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE bv.vendor_id = ?
        ORDER BY b.event_date DESC
    ");
    $stmt->execute([$vendor_id]);
    $vendor_bookings = $stmt->fetchAll();

    // Get vendor statistics
    $total_bookings = count($vendor_bookings);
    $confirmed_bookings = 0;
    $pending_bookings = 0;
    $total_earnings = 0;

    foreach ($vendor_bookings as $booking) {
        if ($booking['status'] === 'confirmed') {
            $confirmed_bookings++;
            $total_earnings += $booking['agreed_price'] ?? 0;
        } elseif ($booking['status'] === 'pending') {
            $pending_bookings++;
        }
    }

    // Get upcoming bookings
    $stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, b.event_time, b.venue_name, 
               u.full_name as customer_name
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE bv.vendor_id = ? AND b.event_date >= CURDATE() AND bv.status = 'confirmed'
        ORDER BY b.event_date ASC
        LIMIT 5
    ");
    $stmt->execute([$vendor_id]);
    $upcoming_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $vendor = null;
    $vendor_bookings = [];
    $upcoming_bookings = [];
    $total_bookings = $confirmed_bookings = $pending_bookings = $total_earnings = 0;
}

// Include layout header
include 'layouts/header.php';
?>

<div class="container-fluid">

    <?php if ($vendor['status'] !== 'active'): ?>
        <!-- Account Status Notice -->
        <div class="card card-warning">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <?php if ($vendor['status'] === 'pending'): ?>
                    <h3>Account Under Review</h3>
                    <p>Your vendor account is currently being reviewed by our administrators. You will receive a notification once your account is approved and you can start receiving bookings.</p>
                <?php else: ?>
                    <h3>Account Inactive</h3>
                    <p>Your vendor account is currently inactive. Please contact our support team for assistance.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x mb-3"></i>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3><?php echo $confirmed_bookings; ?></h3>
                    <p>Confirmed Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($total_earnings, 0); ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <a href="bookings.php?status=pending" class="btn btn-warning btn-block">
                        <i class="fas fa-clock"></i> Review Requests (<?php echo $pending_bookings; ?>)
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="schedule.php" class="btn btn-primary btn-block">
                        <i class="fas fa-calendar-alt"></i> View Schedule
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="portfolio.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-images"></i> Update Portfolio
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="profile.php" class="btn btn-info btn-block">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Business Information</h3>
            <div class="card-tools">
                <a href="profile.php" class="text-primary">Edit <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="text-primary mb-3">Contact Details</h4>
                    <p><i class="fas fa-building text-primary mr-2"></i><strong>Business:</strong> <?php echo htmlspecialchars($vendor['business_name']); ?></p>
                    <p><i class="fas fa-user text-primary mr-2"></i><strong>Owner:</strong> <?php echo htmlspecialchars($vendor['full_name']); ?></p>
                    <p><i class="fas fa-envelope text-primary mr-2"></i><strong>Email:</strong> <?php echo htmlspecialchars($vendor['email']); ?></p>
                    <p><i class="fas fa-phone text-primary mr-2"></i><strong>Phone:</strong> <?php echo htmlspecialchars($vendor['phone'] ?: 'Not provided'); ?></p>
                </div>
                <div class="col-md-6">
                    <h4 class="text-primary mb-3">Service Details</h4>
                    <p><i class="fas fa-cogs text-primary mr-2"></i><strong>Service Type:</strong> <?php echo ucfirst($vendor['service_type']); ?></p>
                    <p><i class="fas fa-star text-primary mr-2"></i><strong>Rating:</strong> <?php echo number_format($vendor['rating'], 1); ?>/5.0 (<?php echo $vendor['total_reviews']; ?> reviews)</p>
                    <p><i class="fas fa-dollar-sign text-primary mr-2"></i><strong>Price Range:</strong> <?php echo htmlspecialchars($vendor['price_range'] ?: 'Contact for pricing'); ?></p>
                    <p><i class="fas fa-info-circle text-primary mr-2"></i><strong>Description:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($vendor['description'] ?: 'No description provided'); ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Bookings and Recent Requests -->
    <div class="row">
        <!-- Upcoming Bookings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Events</h3>
                    <div class="card-tools">
                        <a href="schedule.php" class="text-primary">View Calendar <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_bookings)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                            <p class="text-muted">No upcoming events scheduled.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_bookings as $booking): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($booking['customer_name']); ?></h4>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-calendar mr-1"></i> <?php echo date('M j, Y', strtotime($booking['event_date'])); ?> at
                                        <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                    </p>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($booking['venue_name'] ?: 'TBD'); ?>
                                    </p>
                                </div>
                                <div>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Booking Requests -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Requests</h3>
                    <div class="card-tools">
                        <a href="bookings.php" class="text-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($vendor_bookings)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                            <p class="text-muted">No booking requests yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($vendor_bookings, 0, 5) as $booking): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($booking['customer_name']); ?></h4>
                                    <p class="mb-0 text-muted">
                                        Event: <?php echo date('M j, Y', strtotime($booking['event_date'])); ?>
                                    </p>
                                    <?php if ($booking['agreed_price']): ?>
                                        <p class="mb-0 text-primary font-weight-bold">
                                            RM <?php echo number_format($booking['agreed_price'], 2); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-<?php
                                                                echo $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'danger');
                                                                ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                    <br>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="text-primary small mt-1 d-inline-block">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-line text-primary mr-2"></i> Performance Overview
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <i class="fas fa-percentage fa-2x text-success mb-3"></i>
                    <h4>Acceptance Rate</h4>
                    <p class="h3 text-success"><?php echo $total_bookings > 0 ? round(($confirmed_bookings / $total_bookings) * 100) : 0; ?>%</p>
                    <p class="text-muted">Confirmed vs Total Requests</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-star fa-2x text-primary mb-3"></i>
                    <h4>Average Rating</h4>
                    <p class="h3 text-primary"><?php echo number_format($vendor['rating'], 1); ?>/5.0</p>
                    <p class="text-muted">Based on <?php echo $vendor['total_reviews']; ?> reviews</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-calendar-check fa-2x text-info mb-3"></i>
                    <h4>This Month</h4>
                    <p class="h3 text-info">
                        <?php
                        $this_month = 0;
                        foreach ($vendor_bookings as $booking) {
                            if (date('Y-m', strtotime($booking['event_date'])) === date('Y-m')) {
                                $this_month++;
                            }
                        }
                        echo $this_month;
                        ?>
                    </p>
                    <p class="text-muted">Events this month</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'layouts/footer.php'; ?>