<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Define access constant for layout
define('ADMIN_ACCESS', true);

// Page variables
$page_header = 'Reports & Analytics';
$page_description = 'Business intelligence and performance metrics';

// Get date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';

// Booking Statistics
$booking_stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END), 0) as pending_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END), 0) as completed_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_bookings,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(paid_amount), 0) as collected_revenue,
        COALESCE(AVG(total_amount), 0) as avg_booking_value,
        COALESCE(AVG(guest_count), 0) as avg_guest_count
    FROM bookings 
    WHERE event_date BETWEEN ? AND ?
";
$stmt = $pdo->prepare($booking_stats_query);
$stmt->execute([$start_date, $end_date]);
$booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Revenue Trends (Monthly)
$revenue_trends_query = "
    SELECT 
        DATE_FORMAT(event_date, '%Y-%m') as month,
        COUNT(*) as bookings,
        COALESCE(SUM(total_amount), 0) as revenue,
        COALESCE(SUM(paid_amount), 0) as collected
    FROM bookings 
    WHERE event_date >= DATE_SUB(?, INTERVAL 11 MONTH)
    GROUP BY DATE_FORMAT(event_date, '%Y-%m')
    ORDER BY month ASC
";
$stmt = $pdo->prepare($revenue_trends_query);
$stmt->execute([$end_date]);
$revenue_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Package Performance
$package_performance_query = "
    SELECT 
        COALESCE(wp.name, 'Custom Package') as package_name,
        COUNT(b.id) as bookings_count,
        COALESCE(SUM(b.total_amount), 0) as total_revenue,
        COALESCE(AVG(b.total_amount), 0) as avg_revenue,
        COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count
    FROM bookings b
    LEFT JOIN wedding_packages wp ON b.package_id = wp.id
    WHERE b.event_date BETWEEN ? AND ?
    GROUP BY b.package_id, wp.name
    ORDER BY bookings_count DESC
";
$stmt = $pdo->prepare($package_performance_query);
$stmt->execute([$start_date, $end_date]);
$package_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer Analysis
$customer_analysis_query = "
    SELECT 
        COUNT(DISTINCT u.id) as total_customers,
        COUNT(DISTINCT CASE WHEN b.id IS NOT NULL THEN u.id END) as customers_with_bookings,
        COALESCE(AVG(customer_stats.booking_count), 0) as avg_bookings_per_customer,
        COALESCE(AVG(customer_stats.total_spent), 0) as avg_spent_per_customer
    FROM users u
    LEFT JOIN (
        SELECT 
            customer_id,
            COUNT(*) as booking_count,
            COALESCE(SUM(total_amount), 0) as total_spent
        FROM bookings
        WHERE event_date BETWEEN ? AND ?
        GROUP BY customer_id
    ) customer_stats ON u.id = customer_stats.customer_id
    LEFT JOIN bookings b ON u.id = b.customer_id AND b.event_date BETWEEN ? AND ?
    WHERE u.role = 'customer'
";
$stmt = $pdo->prepare($customer_analysis_query);
$stmt->execute([$start_date, $end_date, $start_date, $end_date]);
$customer_analysis = $stmt->fetch(PDO::FETCH_ASSOC);

// Vendor Performance
$vendor_performance_query = "
    SELECT 
        v.business_name,
        v.service_type,
        COUNT(bv.id) as total_bookings,
        COALESCE(SUM(CASE WHEN bv.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_bookings,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count,
        COALESCE(SUM(bv.agreed_price), 0) as total_earnings
    FROM vendors v
    LEFT JOIN booking_vendors bv ON v.id = bv.vendor_id
    LEFT JOIN bookings b ON bv.booking_id = b.id AND b.event_date BETWEEN ? AND ?
    LEFT JOIN reviews r ON v.id = r.vendor_id
    WHERE v.status = 'active'
    GROUP BY v.id
    HAVING total_bookings > 0
    ORDER BY total_bookings DESC
    LIMIT 10
";
$stmt = $pdo->prepare($vendor_performance_query);
$stmt->execute([$start_date, $end_date]);
$vendor_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment Analysis
$payment_analysis_query = "
    SELECT 
        payment_method,
        COUNT(*) as payment_count,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as avg_amount
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE p.status = 'completed' AND b.event_date BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total_amount DESC
";
$stmt = $pdo->prepare($payment_analysis_query);
$stmt->execute([$start_date, $end_date]);
$payment_analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Customers
$top_customers_query = "
    SELECT 
        u.full_name,
        u.email,
        COUNT(b.id) as booking_count,
        COALESCE(SUM(b.total_amount), 0) as total_spent,
        MAX(b.event_date) as last_booking_date
    FROM users u
    JOIN bookings b ON u.id = b.customer_id
    WHERE b.event_date BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
";
$stmt = $pdo->prepare($top_customers_query);
$stmt->execute([$start_date, $end_date]);
$top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                    <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial</option>
                    <option value="customer" <?php echo $report_type === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="vendor" <?php echo $report_type === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Key Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($booking_stats['total_bookings']); ?></h4>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($booking_stats['total_revenue'], 0); ?></h4>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($booking_stats['collected_revenue'], 0); ?></h4>
                        <p class="mb-0">Collected Revenue</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>RM <?php echo number_format($booking_stats['avg_booking_value'], 0); ?></h4>
                        <p class="mb-0">Avg Booking Value</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Revenue Trend Chart -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Revenue Trend (12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Booking Status Distribution -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Booking Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="bookingStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Package Performance -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Package Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>Avg Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($package_performance as $package): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                    <td><?php echo number_format($package['bookings_count']); ?></td>
                                    <td>RM <?php echo number_format($package['total_revenue'], 0); ?></td>
                                    <td>RM <?php echo number_format($package['avg_revenue'], 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Methods</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Payments</th>
                                <th>Total Amount</th>
                                <th>Avg Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_analysis as $payment): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td><?php echo number_format($payment['payment_count']); ?></td>
                                    <td>RM <?php echo number_format($payment['total_amount'], 0); ?></td>
                                    <td>RM <?php echo number_format($payment['avg_amount'], 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Top Customers -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($customer['booking_count']); ?></td>
                                    <td>RM <?php echo number_format($customer['total_spent'], 0); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($customer['last_booking_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Vendors -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Vendors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Service</th>
                                <th>Bookings</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendor_performance as $vendor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vendor['business_name']); ?></td>
                                    <td><?php echo ucfirst($vendor['service_type']); ?></td>
                                    <td><?php echo number_format($vendor['total_bookings']); ?></td>
                                    <td>
                                        <?php if ($vendor['avg_rating']): ?>
                                            <?php echo number_format($vendor['avg_rating'], 1); ?>/5 (<?php echo $vendor['review_count']; ?>)
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Export Reports</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <button class="btn btn-success w-100" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> Export as Excel
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-secondary w-100" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script>
    // Revenue Trend Chart
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    const revenueTrendChart = new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($revenue_trends, 'month')); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(array_column($revenue_trends, 'revenue')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Collected',
                data: <?php echo json_encode(array_column($revenue_trends, 'collected')); ?>,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Booking Status Chart
    const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
    const bookingStatusChart = new Chart(bookingStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $booking_stats['pending_bookings']; ?>,
                    <?php echo $booking_stats['confirmed_bookings']; ?>,
                    <?php echo $booking_stats['completed_bookings']; ?>,
                    <?php echo $booking_stats['cancelled_bookings']; ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    function exportReport(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', format);
        window.location.href = 'reports.php?' + params.toString();
    }
</script>