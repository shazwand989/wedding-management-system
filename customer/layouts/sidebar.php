<?php
// Ensure this file is included from customer pages
if (!defined('CUSTOMER_ACCESS')) {
    exit('Direct access not allowed');
}
?>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div style="padding: 2rem; text-align: center; border-bottom: 1px solid var(--border-color);">
            <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-heart" style="font-size: 2rem;"></i>
                <h3>My Wedding</h3>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="new-booking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'new-booking.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> New Booking</a></li>
            <li><a href="vendors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : ''; ?>"><i class="fas fa-store"></i> Find Vendors</a></li>
            <li><a href="timeline.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'timeline.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Wedding Timeline</a></li>
            <li><a href="budget.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>"><i class="fas fa-calculator"></i> Budget Tracker</a></li>
            <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i> Profile</a></li>
            <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <div>
                <h1 style="margin: 0; color: var(--primary-color);"><?php echo isset($page_header) ? $page_header : 'My Wedding Dashboard'; ?></h1>
                <p style="margin: 0; color: #666;"><?php echo isset($page_description) ? $page_description : 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!'; ?></p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?><br>
                    <i class="fas fa-clock"></i> <?php echo date('g:i A'); ?>
                </p>
            </div>
        </div>
