<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';
include 'layouts/header.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Customer Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard']
];

$customer_id = $_SESSION['user_id'];


// Get customer's bookings
try {
    $stmt = $pdo->prepare("
        SELECT b.*, wp.name as package_name, wp.features
        FROM bookings b 
        LEFT JOIN wedding_packages wp ON b.package_id = wp.id 
        WHERE b.customer_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $bookings = $stmt->fetchAll();

    // Get upcoming booking
    $stmt = $pdo->prepare("
        SELECT b.*, wp.name as package_name 
        FROM bookings b 
        LEFT JOIN wedding_packages wp ON b.package_id = wp.id 
        WHERE b.customer_id = ? AND b.event_date >= CURDATE() AND b.booking_status IN ('pending', 'confirmed') 
        ORDER BY b.event_date ASC 
        LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    $upcoming_booking = $stmt->fetch();

    // Get customer stats
    $total_spent = 0;
    $total_bookings = count($bookings);
    $completed_bookings = 0;

    foreach ($bookings as $booking) {
        if ($booking['booking_status'] === 'completed') {
            $total_spent += $booking['total_amount'];
            $completed_bookings++;
        }
    }
} catch (PDOException $e) {
    $bookings = [];
    $upcoming_booking = null;
    $total_spent = $total_bookings = $completed_bookings = 0;
}

// Get available packages
try {
    $stmt = $pdo->query("SELECT * FROM wedding_packages WHERE status = 'active' ORDER BY price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}
?>


<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?php echo $completed_bookings; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>RM <?php echo number_format($total_spent, 0); ?></h3>
                    <p>Total Spent</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3><?php echo $upcoming_booking ? 1 : 0; ?></h3>
                    <p>Upcoming Events</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
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
                <div class="col-md-3 col-sm-6">
                    <a href="new-booking.php" class="btn btn-primary btn-block mb-2"><i class="fas fa-plus"></i> Book New Wedding</a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="vendors.php" class="btn btn-secondary btn-block mb-2"><i class="fas fa-search"></i> Find Vendors</a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="payments.php" class="btn btn-warning btn-block mb-2"><i class="fas fa-credit-card"></i> Make Payment</a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="profile.php" class="btn btn-info btn-block mb-2"><i class="fas fa-user-edit"></i> Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($upcoming_booking): ?>
        <!-- Upcoming Wedding -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heart mr-2"></i>Your Upcoming Wedding</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Event Details</h4>
                        <p><i class="fas fa-calendar mr-2"></i><strong>Date:</strong> <?php echo date('F j, Y', strtotime($upcoming_booking['event_date'])); ?></p>
                        <p><i class="fas fa-clock mr-2"></i><strong>Time:</strong> <?php echo date('g:i A', strtotime($upcoming_booking['event_time'])); ?></p>
                        <p><i class="fas fa-map-marker-alt mr-2"></i><strong>Venue:</strong> <?php echo htmlspecialchars($upcoming_booking['venue_name'] ?: 'To be determined'); ?></p>
                        <p><i class="fas fa-users mr-2"></i><strong>Guests:</strong> <?php echo $upcoming_booking['guest_count']; ?> people</p>
                    </div>
                    <div class="col-md-6">
                        <h4>Booking Status</h4>
                        <p><span class="badge badge-<?php echo $upcoming_booking['booking_status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($upcoming_booking['booking_status']); ?>
                            </span></p>
                        <p><strong>Package:</strong> <?php echo htmlspecialchars($upcoming_booking['package_name'] ?: 'Custom Package'); ?></p>
                        <p><strong>Total Amount:</strong> RM <?php echo number_format($upcoming_booking['total_amount'], 2); ?></p>
                        <p><strong>Paid Amount:</strong> RM <?php echo number_format($upcoming_booking['paid_amount'], 2); ?></p>
                        <a href="bookings.php?id=<?php echo $upcoming_booking['id']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> View Details</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Bookings and Available Packages -->
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Bookings</h3>
                    <div class="card-tools">
                        <a href="bookings.php" class="btn btn-tool">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                            <p class="text-muted">No bookings yet!</p>
                            <a href="new-booking.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Your First Booking</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Event Date</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($booking['package_name'] ?: 'Custom'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                                            echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'cancelled' ? 'danger' : 'info'));
                                                                            ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td>RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a>
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

        <!-- Available Packages -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Wedding Packages</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($packages)): ?>
                        <p class="text-center text-muted p-4">No packages available.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($packages, 0, 3) as $package): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($package['name']); ?></h4>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars(substr($package['description'], 0, 100)); ?>...</p>
                                    <p class="font-weight-bold text-primary">RM <?php echo number_format($package['price'], 2); ?></p>
                                </div>
                                <div>
                                    <a href="new-booking.php?package=<?php echo $package['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-heart"></i> Choose</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="new-booking.php" class="btn btn-tool"><i class="fas fa-eye"></i> View All Packages</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Wedding Tips -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Wedding Planning Tips</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <i class="fas fa-calendar-alt fa-2x text-primary mb-3"></i>
                    <h4>Plan Early</h4>
                    <p class="text-muted">Start planning 12-18 months ahead for the best vendor availability and venue options.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-calculator fa-2x text-primary mb-3"></i>
                    <h4>Set Budget</h4>
                    <p class="text-muted">Determine your budget early and allocate funds for different aspects of your wedding.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                    <h4>Trust Vendors</h4>
                    <p class="text-muted">Work with our verified vendors who have experience in creating magical wedding moments.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>