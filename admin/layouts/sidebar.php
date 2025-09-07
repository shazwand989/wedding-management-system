<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <i class="fas fa-heart brand-image img-circle elevation-3" style="opacity: .8; font-size: 2rem; margin-left: 10px; margin-right: 10px;"></i>
        <span class="brand-text font-weight-light">Wedding Admin</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-light"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block text-white"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'); ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">BOOKING MANAGEMENT</li>
                <li class="nav-item">
                    <a href="bookings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>
                            Bookings
                            <span class="badge badge-warning right">
                                <?php
                                // Get pending bookings count
                                try {
                                    if (isset($pdo)) {
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'");
                                        echo $stmt->fetchColumn();
                                    } else {
                                        echo '0';
                                    }
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </span>
                        </p>
                    </a>
                </li>

                <li class="nav-header">USER MANAGEMENT</li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Customers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="vendors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-store"></i>
                        <p>Vendors</p>
                    </a>
                </li>

                <li class="nav-header">SERVICES</li>
                <li class="nav-item">
                    <a href="packages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-box"></i>
                        <p>Wedding Packages</p>
                    </a>
                </li>

                <li class="nav-header">FINANCIAL</li>
                <li class="nav-item">
                    <a href="payments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>Payments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <!-- logout -->
                <li class="nav-header">SYSTEM</li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="logout()">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 golden-text">
                        <?php echo isset($page_header) ? $page_header : 'Admin Dashboard'; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php echo isset($page_description) ? $page_description : 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!'; ?>
                    </p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">
                            <?php echo isset($page_header) ? $page_header : 'Dashboard'; ?>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">