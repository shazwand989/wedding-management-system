<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Earnings';
$page_header = 'Financial Dashboard';
$page_description = 'Track your earnings and financial performance';

$vendor_user_id = $_SESSION['user_id'];

// Get vendor information
try {
    $stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
    $stmt->execute([$vendor_user_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        redirectTo('../login.php');
    }

    $vendor_id = $vendor['id'];

    // Get date filters
    $year_filter = $_GET['year'] ?? date('Y');
    $month_filter = $_GET['month'] ?? '';

    // Build date conditions
    $date_conditions = [];
    $date_params = [$vendor_id];

    if ($year_filter) {
        $date_conditions[] = "YEAR(b.event_date) = ?";
        $date_params[] = $year_filter;
    }

    if ($month_filter) {
        $date_conditions[] = "MONTH(b.event_date) = ?";
        $date_params[] = $month_filter;
    }

    $date_where = !empty($date_conditions) ? 'AND ' . implode(' AND ', $date_conditions) : '';

    // Get earnings statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(bv.agreed_price) as total_earnings,
            AVG(bv.agreed_price) as avg_booking_value,
            COUNT(CASE WHEN MONTH(b.event_date) = MONTH(CURDATE()) AND YEAR(b.event_date) = YEAR(CURDATE()) THEN 1 END) as this_month_bookings,
            SUM(CASE WHEN MONTH(b.event_date) = MONTH(CURDATE()) AND YEAR(b.event_date) = YEAR(CURDATE()) THEN bv.agreed_price ELSE 0 END) as this_month_earnings
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id = ? AND bv.status = 'confirmed' AND bv.agreed_price IS NOT NULL
        $date_where
    ");
    $stmt->execute($date_params);
    $stats = $stmt->fetch();

    // Get monthly earnings for chart
    $monthly_stmt = $pdo->prepare("
        SELECT 
            YEAR(b.event_date) as year,
            MONTH(b.event_date) as month,
            COUNT(*) as bookings,
            SUM(bv.agreed_price) as earnings
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id = ? AND bv.status = 'confirmed' AND bv.agreed_price IS NOT NULL
        AND YEAR(b.event_date) = ?
        GROUP BY YEAR(b.event_date), MONTH(b.event_date)
        ORDER BY YEAR(b.event_date), MONTH(b.event_date)
    ");
    $monthly_stmt->execute([$vendor_id, $year_filter]);
    $monthly_data = $monthly_stmt->fetchAll();

    // Get recent transactions
    $transactions_stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, u.full_name as customer_name, b.booking_status
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE bv.vendor_id = ? AND bv.status = 'confirmed' AND bv.agreed_price IS NOT NULL
        $date_where
        ORDER BY b.event_date DESC
        LIMIT 20
    ");
    $transactions_stmt->execute($date_params);
    $transactions = $transactions_stmt->fetchAll();

    // Get top performing months
    $top_months_stmt = $pdo->prepare("
        SELECT 
            YEAR(b.event_date) as year,
            MONTH(b.event_date) as month,
            COUNT(*) as bookings,
            SUM(bv.agreed_price) as earnings
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        WHERE bv.vendor_id = ? AND bv.status = 'confirmed' AND bv.agreed_price IS NOT NULL
        GROUP BY YEAR(b.event_date), MONTH(b.event_date)
        ORDER BY earnings DESC
        LIMIT 5
    ");
    $top_months_stmt->execute([$vendor_id]);
    $top_months = $top_months_stmt->fetchAll();

    // Prepare chart data
    $chart_labels = [];
    $chart_data = [];
    $chart_bookings = [];

    for ($i = 1; $i <= 12; $i++) {
        $month_names = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        $chart_labels[] = $month_names[$i];
        
        $found = false;
        foreach ($monthly_data as $data) {
            if ($data['month'] == $i) {
                $chart_data[] = (float)$data['earnings'];
                $chart_bookings[] = (int)$data['bookings'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $chart_data[] = 0;
            $chart_bookings[] = 0;
        }
    }

} catch (PDOException $e) {
    $error_message = "Error loading earnings data: " . $e->getMessage();
    $stats = ['total_bookings' => 0, 'total_earnings' => 0, 'avg_booking_value' => 0, 'this_month_bookings' => 0, 'this_month_earnings' => 0];
    $transactions = [];
    $top_months = [];
    $chart_data = array_fill(0, 12, 0);
    $chart_bookings = array_fill(0, 12, 0);
    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
}

// Include layout header
include 'layouts/header.php';
?>

<div class="container-fluid">

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Earnings</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Year:</label>
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Month:</label>
                            <select name="month" class="form-control" onchange="this.form.submit()">
                                <option value="">All Months</option>
                                <?php 
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($months as $num => $name): 
                                ?>
                                    <option value="<?php echo $num; ?>" <?php echo $month_filter == $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <a href="earnings.php" class="btn btn-secondary">Reset Filters</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Earnings Statistics -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($stats['total_earnings'] ?: 0, 0); ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x mb-3"></i>
                    <h3><?php echo $stats['total_bookings'] ?: 0; ?></h3>
                    <p>Completed Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($stats['avg_booking_value'] ?: 0, 0); ?></h3>
                    <p>Average Booking</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($stats['this_month_earnings'] ?: 0, 0); ?></h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Earnings Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Monthly Earnings - <?php echo $year_filter; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" style="height: 300px;"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h5>No Transactions Found</h5>
                            <p class="text-muted">No earnings data found for the selected period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Event Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['event_date'])); ?></td>
                                            <td>
                                                <strong class="text-success">RM <?php echo number_format($transaction['agreed_price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $transaction['booking_status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($transaction['booking_status']); ?>
                                                </span>
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

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- This Month Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">This Month Summary</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-calendar-check text-primary"></i> Bookings:</span>
                        <strong><?php echo $stats['this_month_bookings'] ?: 0; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-dollar-sign text-success"></i> Earnings:</span>
                        <strong class="text-success">RM <?php echo number_format($stats['this_month_earnings'] ?: 0, 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-line text-info"></i> Avg per Booking:</span>
                        <strong>
                            RM <?php 
                            $avg_this_month = $stats['this_month_bookings'] > 0 ? 
                                $stats['this_month_earnings'] / $stats['this_month_bookings'] : 0;
                            echo number_format($avg_this_month, 2);
                            ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Top Performing Months -->
            <?php if (!empty($top_months)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Performing Months</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $month_names = [
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                    ];
                    foreach ($top_months as $index => $month): 
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 <?php echo $index < count($top_months) - 1 ? 'border-bottom pb-3' : ''; ?>">
                            <div>
                                <strong><?php echo $month_names[$month['month']] . ' ' . $month['year']; ?></strong><br>
                                <small class="text-muted"><?php echo $month['bookings']; ?> bookings</small>
                            </div>
                            <div class="text-right">
                                <strong class="text-success">RM <?php echo number_format($month['earnings'], 0); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Financial Goals -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Financial Insights</h3>
                </div>
                <div class="card-body">
                    <?php
                    $yearly_goal = 50000; // Set a yearly goal
                    $progress = $stats['total_earnings'] ? ($stats['total_earnings'] / $yearly_goal) * 100 : 0;
                    ?>
                    <h6>Yearly Goal Progress</h6>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" style="width: <?php echo min($progress, 100); ?>%"></div>
                    </div>
                    <small class="text-muted">
                        RM <?php echo number_format($stats['total_earnings'] ?: 0, 0); ?> of RM <?php echo number_format($yearly_goal, 0); ?> goal
                        (<?php echo number_format($progress, 1); ?>%)
                    </small>

                    <hr>

                    <h6 class="mt-3">Quick Stats</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary"><?php echo date('j'); ?></h4>
                                <small>Days this month</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">
                                <?php echo $stats['this_month_bookings'] ? 
                                    number_format($stats['this_month_earnings'] / date('j'), 0) : 0; ?>
                            </h4>
                            <small>Daily avg</small>
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
                    <a href="bookings.php" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-calendar-check"></i> View Bookings
                    </a>
                    <a href="services.php" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-cogs"></i> Update Services
                    </a>
                    <button class="btn btn-success btn-block" onclick="exportEarnings()">
                        <i class="fas fa-file-excel"></i> Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize earnings chart
    const ctx = document.getElementById('earningsChart').getContext('2d');
    const earningsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Earnings (RM)',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }, {
                label: 'Bookings',
                data: <?php echo json_encode($chart_bookings); ?>,
                type: 'line',
                yAxisID: 'y1',
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Earnings (RM)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Bookings'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label === 'Earnings (RM)') {
                                return context.dataset.label + ': RM ' + context.parsed.y.toLocaleString();
                            }
                            return context.dataset.label + ': ' + context.parsed.y;
                        }
                    }
                }
            }
        }
    });
});

function exportTransactions() {
    const year = '<?php echo $year_filter; ?>';
    const month = '<?php echo $month_filter; ?>';
    window.open(`../includes/export.php?type=transactions&vendor_id=<?php echo $vendor_id; ?>&year=${year}&month=${month}`, '_blank');
}

function exportEarnings() {
    const year = '<?php echo $year_filter; ?>';
    window.open(`../includes/export.php?type=earnings&vendor_id=<?php echo $vendor_id; ?>&year=${year}`, '_blank');
}
</script>

<?php include 'layouts/footer.php'; ?>
