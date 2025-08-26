<?php
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

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
        // This shouldn't happen, but just in case
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div style="padding: 2rem; text-align: center; border-bottom: 1px solid var(--border-color);">
            <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-store" style="font-size: 2rem;"></i>
                <h3>Vendor Portal</h3>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="portfolio.php"><i class="fas fa-images"></i> Portfolio</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <div>
                <h1 style="margin: 0; color: var(--primary-color);">Vendor Dashboard</h1>
                <p style="margin: 0; color: #666;">Welcome back, <?php echo htmlspecialchars($vendor['business_name']); ?>!</p>
            </div>
            <div style="text-align: right;">
                <?php if ($vendor['status'] === 'pending'): ?>
                    <div class="alert alert-warning" style="margin: 0; padding: 0.5rem 1rem;">
                        <i class="fas fa-clock"></i> Account pending approval
                    </div>
                <?php elseif ($vendor['status'] === 'active'): ?>
                    <div class="alert alert-success" style="margin: 0; padding: 0.5rem 1rem;">
                        <i class="fas fa-check"></i> Account active
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" style="margin: 0; padding: 0.5rem 1rem;">
                        <i class="fas fa-times"></i> Account inactive
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($vendor['status'] !== 'active'): ?>
        <!-- Account Status Notice -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, var(--warning-color), #fff3cd);">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning-color); margin-bottom: 1rem;"></i>
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
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $total_bookings; ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $pending_bookings; ?></span>
                <span class="stat-label">Pending Requests</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number"><?php echo $confirmed_bookings; ?></span>
                <span class="stat-label">Confirmed Bookings</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.8;"></i>
                <span class="stat-number">RM <?php echo number_format($total_earnings, 0); ?></span>
                <span class="stat-label">Total Earnings</span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="bookings.php?status=pending" class="btn btn-warning">
                    <i class="fas fa-clock"></i> Review Requests (<?php echo $pending_bookings; ?>)
                </a>
                <a href="schedule.php" class="btn">
                    <i class="fas fa-calendar-alt"></i> View Schedule
                </a>
                <a href="portfolio.php" class="btn btn-secondary">
                    <i class="fas fa-images"></i> Update Portfolio
                </a>
                <a href="profile.php" class="btn" style="background-color: var(--info-color);">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <!-- Vendor Information -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Business Information</h3>
                <a href="profile.php" style="color: var(--primary-color);">Edit →</a>
            </div>
            <div class="grid grid-2">
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Contact Details</h4>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-building" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Business:</strong> <?php echo htmlspecialchars($vendor['business_name']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-user" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Owner:</strong> <?php echo htmlspecialchars($vendor['full_name']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Email:</strong> <?php echo htmlspecialchars($vendor['email']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-phone" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($vendor['phone'] ?: 'Not provided'); ?>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Service Details</h4>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-cogs" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Service Type:</strong> <?php echo ucfirst($vendor['service_type']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-star" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Rating:</strong> <?php echo number_format($vendor['rating'], 1); ?>/5.0 (<?php echo $vendor['total_reviews']; ?> reviews)
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-dollar-sign" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Price Range:</strong> <?php echo htmlspecialchars($vendor['price_range'] ?: 'Contact for pricing'); ?>
                    </div>
                    <div>
                        <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <strong>Description:</strong><br>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                            <?php echo htmlspecialchars($vendor['description'] ?: 'No description provided'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Upcoming Bookings -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Events</h3>
                    <a href="schedule.php" style="color: var(--primary-color);">View Calendar →</a>
                </div>
                <?php if (empty($upcoming_bookings)): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                        <p style="color: #666;">No upcoming events scheduled.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcoming_bookings as $booking): ?>
                    <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($booking['customer_name']); ?></h4>
                            <p style="margin: 0; font-size: 0.9rem; color: #666;">
                                <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($booking['event_date'])); ?> at 
                                <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                            </p>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['venue_name'] ?: 'TBD'); ?>
                            </p>
                        </div>
                        <div>
                            <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Booking Requests -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Requests</h3>
                    <a href="bookings.php" style="color: var(--primary-color);">View All →</a>
                </div>
                <?php if (empty($vendor_bookings)): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-handshake" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                        <p style="color: #666;">No booking requests yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($vendor_bookings, 0, 5) as $booking): ?>
                    <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($booking['customer_name']); ?></h4>
                            <p style="margin: 0; font-size: 0.9rem; color: #666;">
                                Event: <?php echo date('M j, Y', strtotime($booking['event_date'])); ?>
                            </p>
                            <?php if ($booking['agreed_price']): ?>
                            <p style="margin: 0.5rem 0 0 0; font-weight: bold; color: var(--primary-color);">
                                RM <?php echo number_format($booking['agreed_price'], 2); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-<?php 
                                echo $booking['status'] === 'confirmed' ? 'success' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                            <br>
                            <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" style="color: var(--primary-color); font-size: 0.8rem; margin-top: 0.5rem; display: inline-block;">
                                View Details →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line" style="color: var(--primary-color);"></i> 
                    Performance Overview
                </h3>
            </div>
            <div class="grid grid-3">
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-percentage" style="font-size: 2rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                    <h4>Acceptance Rate</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: var(--success-color);">
                        <?php echo $total_bookings > 0 ? round(($confirmed_bookings / $total_bookings) * 100) : 0; ?>%
                    </p>
                    <p style="font-size: 0.9rem; color: #666;">Confirmed vs Total Requests</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-star" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Average Rating</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                        <?php echo number_format($vendor['rating'], 1); ?>/5.0
                    </p>
                    <p style="font-size: 0.9rem; color: #666;">Based on <?php echo $vendor['total_reviews']; ?> reviews</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-calendar-check" style="font-size: 2rem; color: var(--info-color); margin-bottom: 1rem;"></i>
                    <h4>This Month</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: var(--info-color);">
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
                    <p style="font-size: 0.9rem; color: #666;">Events this month</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
