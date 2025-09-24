<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

define('ADMIN_ACCESS', true);

$page_header = 'Visitor Analytics';
$page_description = 'Monitor website visitors and traffic analytics';

// Get analytics data
require_once '../includes/visitor_tracker.php';
$analytics = VisitorTracker::getAnalytics($pdo, 30);

// Handle AJAX requests for real-time data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'stats') {
    header('Content-Type: application/json');
    
    $stats = [
        'today_visitors' => $analytics['today']['today_visitors'] ?? 0,
        'today_visits' => $analytics['today']['today_visits'] ?? 0,
        'today_page_views' => $analytics['today']['today_page_views'] ?? 0,
        'online_now' => 0 // We'll calculate this
    ];
    
    // Calculate visitors online now (last 5 minutes)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT session_id) as online_now 
        FROM visitor_sessions 
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $online = $stmt->fetch();
    $stats['online_now'] = $online['online_now'] ?? 0;
    
    echo json_encode($stats);
    exit();
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    
    <!-- Real-time Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="today-visitors"><?php echo $analytics['today']['today_visitors'] ?? 0; ?></h4>
                            <p class="mb-0">Today's Visitors</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="today-visits"><?php echo $analytics['today']['today_visits'] ?? 0; ?></h4>
                            <p class="mb-0">Today's Visits</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-eye fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="today-pageviews"><?php echo $analytics['today']['today_page_views'] ?? 0; ?></h4>
                            <p class="mb-0">Page Views</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="online-now">0</h4>
                            <p class="mb-0">Online Now</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Visitor Trends (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="visitorChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Device Types</h5>
                </div>
                <div class="card-body">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tables -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-globe"></i> Top Browsers</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($analytics['browser_breakdown'])): ?>
                        <p class="text-muted text-center">No browser data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Browser</th>
                                        <th class="text-right">Visits</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_browser_visits = array_sum(array_column($analytics['browser_breakdown'], 'count'));
                                    foreach ($analytics['browser_breakdown'] as $browser): 
                                        $percentage = $total_browser_visits > 0 ? ($browser['count'] / $total_browser_visits) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <i class="fab fa-<?php echo strtolower($browser['browser_name']); ?> mr-1"></i>
                                                <?php echo htmlspecialchars($browser['browser_name']); ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format($browser['count']); ?></td>
                                            <td class="text-right"><?php echo number_format($percentage, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file"></i> Top Pages</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($analytics['top_pages'])): ?>
                        <p class="text-muted text-center">No page data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th class="text-right">Views</th>
                                        <th class="text-right">Unique</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['top_pages'] as $page): ?>
                                        <tr>
                                            <td>
                                                <code class="small"><?php echo htmlspecialchars($page['page_url']); ?></code>
                                            </td>
                                            <td class="text-right"><?php echo number_format($page['views']); ?></td>
                                            <td class="text-right"><?php echo number_format($page['unique_views']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Visitors -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Visitors (Last 24 Hours)</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($analytics['recent_visitors'])): ?>
                <div class="text-center p-4">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h6>No Recent Visitors</h6>
                    <p class="text-muted">No visitors have been recorded in the last 24 hours.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Device</th>
                                <th>Browser</th>
                                <th>OS</th>
                                <th>Location</th>
                                <th>Pages</th>
                                <th>Duration</th>
                                <th>First Visit</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['recent_visitors'] as $visitor): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($visitor['ip_address']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $visitor['device_type'] === 'mobile' ? 'success' : 
                                                ($visitor['device_type'] === 'tablet' ? 'info' : 
                                                ($visitor['device_type'] === 'desktop' ? 'primary' : 'secondary'));
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo $visitor['device_type'] === 'mobile' ? 'mobile-alt' : 
                                                    ($visitor['device_type'] === 'tablet' ? 'tablet-alt' : 
                                                    ($visitor['device_type'] === 'desktop' ? 'desktop' : 'question'));
                                            ?>"></i>
                                            <?php echo ucfirst($visitor['device_type']); ?>
                                        </span>
                                        <?php if ($visitor['device_name']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($visitor['device_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($visitor['browser_name']); ?>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($visitor['operating_system']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($visitor['country']): ?>
                                            <span class="small"><?php echo htmlspecialchars($visitor['country']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-light"><?php echo $visitor['page_views']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $duration = $visitor['visit_duration'];
                                        if ($duration > 60) {
                                            echo floor($duration / 60) . 'm ' . ($duration % 60) . 's';
                                        } else {
                                            echo $duration . 's';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo date('M j, H:i', strtotime($visitor['created_at'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo date('M j, H:i', strtotime($visitor['last_activity'])); ?></span>
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

<?php include 'layouts/footer.php'; ?>

<!-- Chart.js for analytics charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    
    // Visitor Trends Chart
    const visitorData = <?php echo json_encode(array_reverse($analytics['daily_stats'])); ?>;
    const ctx1 = document.getElementById('visitorChart').getContext('2d');
    
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: visitorData.map(d => d.date),
            datasets: [
                {
                    label: 'Unique Visitors',
                    data: visitorData.map(d => d.unique_visitors),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Total Visits',
                    data: visitorData.map(d => d.total_visits),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Page Views',
                    data: visitorData.map(d => d.total_page_views),
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Device Chart
    const deviceData = <?php echo json_encode($analytics['device_breakdown']); ?>;
    const ctx2 = document.getElementById('deviceChart').getContext('2d');
    
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: deviceData.map(d => d.device_type.charAt(0).toUpperCase() + d.device_type.slice(1)),
            datasets: [{
                data: deviceData.map(d => d.count),
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Auto-refresh stats every 30 seconds
    function refreshStats() {
        $.ajax({
            url: 'visitor-analytics.php?ajax=stats',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#today-visitors').text(data.today_visitors);
                $('#today-visits').text(data.today_visits);
                $('#today-pageviews').text(data.today_page_views);
                $('#online-now').text(data.online_now);
            },
            error: function() {
                console.log('Failed to refresh stats');
            }
        });
    }

    // Initial load of online users
    refreshStats();
    
    // Refresh every 30 seconds
    setInterval(refreshStats, 30000);
});
</script>