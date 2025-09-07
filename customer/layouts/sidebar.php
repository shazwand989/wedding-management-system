<?php
// Ensure this file is included from customer pages
if (!defined('CUSTOMER_ACCESS')) {
    exit('Direct access not allowed');
}
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <i class="fas fa-heart brand-image img-circle elevation-3" style="opacity: .8"></i>
        <span class="brand-text font-weight-light">My Wedding</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-light"></i>
            </div>
            <div class="info">
                <a href="profile.php" class="d-block"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?></a>
            </div>
        </div>
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Bookings -->
                <li class="nav-item">
                    <a href="bookings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>
                            My Bookings
                            <?php
                            // Get pending bookings count (you may need to adjust this query)
                            try {
                                $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status = 'pending'");
                                $pending_stmt->execute([$_SESSION['user_id']]);
                                $pending_count = $pending_stmt->fetchColumn();
                                if ($pending_count > 0) {
                                    echo '<span class="badge badge-info right">' . $pending_count . '</span>';
                                }
                            } catch (Exception $e) {
                                // Handle silently
                            }
                            ?>
                        </p>
                    </a>
                </li>

                <!-- New Booking -->
                <li class="nav-item">
                    <a href="new-booking.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'new-booking.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>New Booking</p>
                    </a>
                </li>

                <!-- Find Vendors -->
                <li class="nav-item">
                    <a href="vendors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-store"></i>
                        <p>Find Vendors</p>
                    </a>
                </li>

                <!-- Wedding Planning -->
                <li class="nav-header">WEDDING PLANNING</li>
                
                <!-- Timeline -->
                <li class="nav-item">
                    <a href="timeline.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'timeline.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Wedding Timeline</p>
                    </a>
                </li>

                <!-- Budget -->
                <li class="nav-item">
                    <a href="budget.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calculator"></i>
                        <p>Budget Tracker</p>
                    </a>
                </li>

                <!-- Profile -->
                <li class="nav-header">ACCOUNT</li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-edit"></i>
                        <p>Profile</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <a href="../includes/logout.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo isset($page_title) ? $page_title : 'Customer Dashboard'; ?></h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <?php if (isset($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $breadcrumb): ?>
                                <?php if (isset($breadcrumb['url'])): ?>
                                    <li class="breadcrumb-item"><a href="<?php echo $breadcrumb['url']; ?>"><?php echo $breadcrumb['title']; ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $breadcrumb['title']; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></li>
                        <?php endif; ?>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
