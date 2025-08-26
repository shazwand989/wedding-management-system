<?php
// Ensure this file is included from vendor pages
if (!defined('VENDOR_ACCESS')) {
    exit('Direct access not allowed');
}
?>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div style="padding: 2rem; text-align: center; border-bottom: 1px solid var(--border-color);">
            <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-store" style="font-size: 2rem;"></i>
                <h3>Vendor Portal</h3>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>"><i class="fas fa-concierge-bell"></i> My Services</a></li>
            <li><a href="calendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="earnings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'active' : ''; ?>"><i class="fas fa-dollar-sign"></i> Earnings</a></li>
            <li><a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i> Profile</a></li>
            <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <div>
                <h1 style="margin: 0; color: var(--primary-color);"><?php echo isset($page_header) ? $page_header : 'Vendor Dashboard'; ?></h1>
                <p style="margin: 0; color: #666;"><?php echo isset($page_description) ? $page_description : 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!'; ?></p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?><br>
                    <i class="fas fa-clock"></i> <?php echo date('g:i A'); ?>
                </p>
            </div>
        </div>
