<?php
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div style="padding: 2rem; text-align: center; border-bottom: 1px solid var(--border-color);">
            <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-heart" style="font-size: 2rem;"></i>
                <h3>My Wedding</h3>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="new-booking.php"><i class="fas fa-plus"></i> New Booking</a></li>
            <li><a href="vendors.php"><i class="fas fa-store"></i> Find Vendors</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <div>
                <h1 style="margin: 0; color: var(--primary-color);">Welcome back!</h1>
                <p style="margin: 0; color: #666;">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Let's plan your perfect wedding!</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?><br>
                    <i class="fas fa-clock"></i> <?php echo date('g:i A'); ?>
                </p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $total_bookings; ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $completed_bookings; ?></span>
                <span class="stat-label">Completed Events</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number">RM <?php echo number_format($total_spent, 0); ?></span>
                <span class="stat-label">Total Spent</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-heart" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $upcoming_booking ? '1' : '0'; ?></span>
                <span class="stat-label">Upcoming Events</span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="new-booking.php" class="btn">
                    <i class="fas fa-plus"></i> Book New Wedding
                </a>
                <a href="vendors.php" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Find Vendors
                </a>
                <a href="payments.php" class="btn" style="background-color: var(--warning-color); color: var(--text-color);">
                    <i class="fas fa-credit-card"></i> Make Payment
                </a>
                <a href="profile.php" class="btn" style="background-color: var(--info-color);">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <?php if ($upcoming_booking): ?>
        <!-- Upcoming Wedding -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, var(--secondary-color), #fff);">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-heart" style="color: var(--primary-color);"></i> 
                    Your Upcoming Wedding
                </h3>
            </div>
            <div class="grid grid-2">
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Event Details</h4>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-calendar" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($upcoming_booking['event_date'])); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-clock" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($upcoming_booking['event_time'])); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Venue:</strong> <?php echo htmlspecialchars($upcoming_booking['venue_name'] ?: 'To be determined'); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-users" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Guests:</strong> <?php echo $upcoming_booking['guest_count']; ?> people
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Booking Status</h4>
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-<?php 
                            echo $upcoming_booking['booking_status'] === 'confirmed' ? 'success' : 
                                ($upcoming_booking['booking_status'] === 'pending' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo ucfirst($upcoming_booking['booking_status']); ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Package:</strong> <?php echo htmlspecialchars($upcoming_booking['package_name'] ?: 'Custom Package'); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Total Amount:</strong> RM <?php echo number_format($upcoming_booking['total_amount'], 2); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Paid Amount:</strong> RM <?php echo number_format($upcoming_booking['paid_amount'], 2); ?>
                    </div>
                    <div>
                        <a href="bookings.php?id=<?php echo $upcoming_booking['id']; ?>" class="btn">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-2">
            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Bookings</h3>
                    <a href="bookings.php" style="color: var(--primary-color);">View All →</a>
                </div>
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar-plus" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                        <p style="color: #666; margin-bottom: 1rem;">No bookings yet!</p>
                        <a href="new-booking.php" class="btn">
                            <i class="fas fa-plus"></i> Create Your First Booking
                        </a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
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
                                            echo $booking['booking_status'] === 'confirmed' ? 'success' : 
                                                ($booking['booking_status'] === 'pending' ? 'warning' : 
                                                ($booking['booking_status'] === 'cancelled' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td>
                                        <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Packages -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Wedding Packages</h3>
                </div>
                <?php if (empty($packages)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No packages available.</p>
                <?php else: ?>
                    <?php foreach (array_slice($packages, 0, 3) as $package): ?>
                    <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($package['name']); ?></h4>
                            <p style="margin: 0; font-size: 0.9rem; color: #666;">
                                <?php echo htmlspecialchars(substr($package['description'], 0, 100)); ?>...
                            </p>
                            <p style="margin: 0.5rem 0 0 0; font-weight: bold; color: var(--primary-color);">
                                RM <?php echo number_format($package['price'], 2); ?>
                            </p>
                        </div>
                        <div>
                            <a href="new-booking.php?package=<?php echo $package['id']; ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                <i class="fas fa-heart"></i> Choose
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; padding: 1rem;">
                        <a href="new-booking.php" style="color: var(--primary-color);">
                            <i class="fas fa-eye"></i> View All Packages →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Wedding Tips -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb" style="color: var(--primary-color);"></i> 
                    Wedding Planning Tips
                </h3>
            </div>
            <div class="grid grid-3">
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-calendar-alt" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Plan Early</h4>
                    <p style="font-size: 0.9rem; color: #666;">Start planning 12-18 months ahead for the best vendor availability and venue options.</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-calculator" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Set Budget</h4>
                    <p style="font-size: 0.9rem; color: #666;">Determine your budget early and allocate funds for different aspects of your wedding.</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-users" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Trust Vendors</h4>
                    <p style="font-size: 0.9rem; color: #666;">Work with our verified vendors who have experience in creating magical wedding moments.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
