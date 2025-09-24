<?php
/**
 * Visitor Analytics Widget
 * Include this in dashboard pages to show visitor stats
 */

// Only load if we have database connection
if (isset($pdo)) {
    try {
        // Get today's visitor stats
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT session_id) as today_visitors,
                COUNT(*) as today_visits,
                SUM(page_views) as today_page_views,
                COUNT(DISTINCT ip_address) as unique_ips,
                AVG(visit_duration) as avg_duration
            FROM visitor_sessions 
            WHERE DATE(created_at) = CURDATE()
        ");
        $today_stats = $stmt->fetch();

        // Get online visitors (last 5 minutes)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT session_id) as online_now 
            FROM visitor_sessions 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $online_stats = $stmt->fetch();

        // Get device breakdown for today
        $stmt = $pdo->query("
            SELECT 
                device_type,
                COUNT(*) as count
            FROM visitor_sessions 
            WHERE DATE(created_at) = CURDATE()
            GROUP BY device_type
            ORDER BY count DESC
        ");
        $device_stats = $stmt->fetchAll();

        // Get recent visitors (last 2 hours)
        $stmt = $pdo->query("
            SELECT 
                ip_address, 
                device_type, 
                browser_name, 
                country,
                page_views,
                created_at
            FROM visitor_sessions 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recent_visitors = $stmt->fetchAll();

    } catch (Exception $e) {
        // Silently handle errors
        $today_stats = ['today_visitors' => 0, 'today_visits' => 0, 'today_page_views' => 0, 'unique_ips' => 0, 'avg_duration' => 0];
        $online_stats = ['online_now' => 0];
        $device_stats = [];
        $recent_visitors = [];
    }
}
?>

<!-- Visitor Analytics Widget -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Website Analytics - Today</h6>
                <a href="visitor-analytics.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> View Details
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Quick Stats -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="mb-0" id="widget-visitors"><?php echo number_format($today_stats['today_visitors'] ?? 0); ?></h6>
                                <small class="text-muted">Visitors</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="mb-0" id="widget-visits"><?php echo number_format($today_stats['today_visits'] ?? 0); ?></h6>
                                <small class="text-muted">Visits</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="mb-0"><?php echo number_format($today_stats['today_page_views'] ?? 0); ?></h6>
                                <small class="text-muted">Page Views</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-circle pulse"></i>
                            </div>
                            <div class="ml-3">
                                <h6 class="mb-0" id="widget-online"><?php echo number_format($online_stats['online_now'] ?? 0); ?></h6>
                                <small class="text-muted">Online Now</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device Breakdown -->
                <?php if (!empty($device_stats)): ?>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="mb-2">Device Types</h6>
                        <?php 
                        $total_devices = array_sum(array_column($device_stats, 'count'));
                        foreach ($device_stats as $device): 
                            $percentage = $total_devices > 0 ? ($device['count'] / $total_devices) * 100 : 0;
                            $device_icon = $device['device_type'] === 'mobile' ? 'mobile-alt' : 
                                          ($device['device_type'] === 'tablet' ? 'tablet-alt' : 
                                          ($device['device_type'] === 'desktop' ? 'desktop' : 'question'));
                        ?>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">
                                    <i class="fas fa-<?php echo $device_icon; ?> mr-1"></i>
                                    <?php echo ucfirst($device['device_type']); ?>
                                </span>
                                <span class="small">
                                    <?php echo $device['count']; ?> (<?php echo number_format($percentage, 1); ?>%)
                                </span>
                            </div>
                            <div class="progress mb-2" style="height: 4px;">
                                <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Recent Visitors -->
                    <div class="col-md-6">
                        <h6 class="mb-2">Recent Visitors</h6>
                        <div style="max-height: 150px; overflow-y: auto;">
                            <?php if (empty($recent_visitors)): ?>
                                <p class="small text-muted">No recent visitors</p>
                            <?php else: ?>
                                <?php foreach ($recent_visitors as $visitor): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <div class="small">
                                            <i class="fas fa-<?php 
                                                echo $visitor['device_type'] === 'mobile' ? 'mobile-alt' : 
                                                    ($visitor['device_type'] === 'tablet' ? 'tablet-alt' : 
                                                    ($visitor['device_type'] === 'desktop' ? 'desktop' : 'question'));
                                            ?> mr-1"></i>
                                            <code class="small"><?php echo htmlspecialchars(substr($visitor['ip_address'], 0, 12)); ?>...</code>
                                            <br>
                                            <span class="text-muted">
                                                <?php echo htmlspecialchars($visitor['browser_name']); ?>
                                                <?php if ($visitor['country']): ?>
                                                    | <?php echo htmlspecialchars($visitor['country']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="text-right small">
                                            <div><?php echo $visitor['page_views']; ?> pages</div>
                                            <div class="text-muted"><?php echo date('H:i', strtotime($visitor['created_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>

<script>
// Auto-refresh visitor widget stats every 60 seconds
function refreshVisitorWidget() {
    $.ajax({
        url: 'visitor-analytics.php?ajax=stats',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#widget-visitors').text(data.today_visitors.toLocaleString());
            $('#widget-visits').text(data.today_visits.toLocaleString());
            $('#widget-online').text(data.online_now.toLocaleString());
        },
        error: function() {
            // Silently ignore errors
        }
    });
}

// Refresh every minute
if (typeof visitorWidgetInterval === 'undefined') {
    var visitorWidgetInterval = setInterval(refreshVisitorWidget, 60000);
}
</script>