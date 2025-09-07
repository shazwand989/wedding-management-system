<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <i class="fas fa-store brand-image img-circle elevation-3" style="opacity: .8; font-size: 2rem; margin-left: 10px; margin-right: 10px;"></i>
        <span class="brand-text font-weight-light">Vendor Portal</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-light"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block text-white"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Vendor'); ?></a>
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
                            My Bookings
                            <span class="badge badge-info right">
                                <?php
                                // Get pending bookings count for this vendor
                                try {
                                    if (isset($pdo) && isset($_SESSION['user_id'])) {
                                        $vendor_id_stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
                                        $vendor_id_stmt->execute([$_SESSION['user_id']]);
                                        $vendor_data = $vendor_id_stmt->fetch();
                                        if ($vendor_data) {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_vendors bv JOIN bookings b ON bv.booking_id = b.id WHERE bv.vendor_id = ? AND bv.status = 'pending'");
                                            $stmt->execute([$vendor_data['id']]);
                                            echo $stmt->fetchColumn();
                                        } else {
                                            echo '0';
                                        }
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

                <li class="nav-header">SERVICES</li>
                <li class="nav-item">
                    <a href="services.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>My Services</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="calendar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Calendar</p>
                    </a>
                </li>

                <li class="nav-header">FINANCIAL</li>
                <li class="nav-item">
                    <a href="earnings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-dollar-sign"></i>
                        <p>Earnings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-star"></i>
                        <p>Reviews</p>
                    </a>
                </li>

                <li class="nav-header">ACCOUNT</li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-edit"></i>
                        <p>Profile</p>
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
                    <h1 class="m-0 blue-text">
                        <?php echo isset($page_header) ? $page_header : 'Vendor Dashboard'; ?>
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
