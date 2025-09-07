<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Calendar';
$page_header = 'Event Calendar';
$page_description = 'View your schedule and manage availability';

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

    // Get current month and year
    $current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    // Ensure valid month and year
    if ($current_month < 1 || $current_month > 12) {
        $current_month = date('n');
    }
    if ($current_year < 2020 || $current_year > 2030) {
        $current_year = date('Y');
    }

    // Get bookings for the current month
    $stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, b.event_time, b.venue_name, 
               u.full_name as customer_name, b.booking_status
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE bv.vendor_id = ? 
        AND YEAR(b.event_date) = ? 
        AND MONTH(b.event_date) = ?
        AND bv.status = 'confirmed'
        ORDER BY b.event_date, b.event_time
    ");
    $stmt->execute([$vendor_id, $current_year, $current_month]);
    $bookings = $stmt->fetchAll();

    // Get upcoming events for sidebar
    $stmt = $pdo->prepare("
        SELECT bv.*, b.event_date, b.event_time, b.venue_name, 
               u.full_name as customer_name
        FROM booking_vendors bv
        JOIN bookings b ON bv.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE bv.vendor_id = ? 
        AND b.event_date >= CURDATE()
        AND bv.status = 'confirmed'
        ORDER BY b.event_date, b.event_time
        LIMIT 10
    ");
    $stmt->execute([$vendor_id]);
    $upcoming_events = $stmt->fetchAll();

    // Create calendar array
    $calendar_data = [];
    foreach ($bookings as $booking) {
        $day = date('j', strtotime($booking['event_date']));
        if (!isset($calendar_data[$day])) {
            $calendar_data[$day] = [];
        }
        $calendar_data[$day][] = $booking;
    }

} catch (PDOException $e) {
    $error_message = "Error loading calendar: " . $e->getMessage();
    $bookings = [];
    $upcoming_events = [];
    $calendar_data = [];
}

// Calendar navigation
$prev_month = $current_month - 1;
$prev_year = $current_year;
$next_month = $current_month + 1;
$next_year = $current_year;

if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get month name
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$current_month_name = $month_names[$current_month];

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

    <div class="row">
        <!-- Main Calendar -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i> <?php echo $current_month_name . ' ' . $current_year; ?>
                        </h3>
                        <div class="btn-group">
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">
                                Today
                            </a>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="calendar-container">
                        <?php
                        // Calendar generation
                        $first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
                        $first_day_of_week = date('w', $first_day);
                        $days_in_month = date('t', $first_day);
                        $today = date('j');
                        $today_month = date('n');
                        $today_year = date('Y');
                        ?>
                        
                        <table class="table table-bordered calendar-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center p-2">Sun</th>
                                    <th class="text-center p-2">Mon</th>
                                    <th class="text-center p-2">Tue</th>
                                    <th class="text-center p-2">Wed</th>
                                    <th class="text-center p-2">Thu</th>
                                    <th class="text-center p-2">Fri</th>
                                    <th class="text-center p-2">Sat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $day_counter = 1;
                                for ($week = 0; $week < 6; $week++) {
                                    echo "<tr>";
                                    for ($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
                                        if ($week == 0 && $day_of_week < $first_day_of_week) {
                                            echo "<td class='calendar-day empty-day'></td>";
                                        } elseif ($day_counter <= $days_in_month) {
                                            $is_today = ($day_counter == $today && $current_month == $today_month && $current_year == $today_year);
                                            $has_events = isset($calendar_data[$day_counter]);
                                            
                                            $cell_class = 'calendar-day';
                                            if ($is_today) $cell_class .= ' today';
                                            if ($has_events) $cell_class .= ' has-events';
                                            
                                            echo "<td class='$cell_class' data-day='$day_counter'>";
                                            echo "<div class='day-number'>$day_counter</div>";
                                            
                                            if ($has_events) {
                                                echo "<div class='events'>";
                                                foreach ($calendar_data[$day_counter] as $event) {
                                                    $time = date('g:i A', strtotime($event['event_time']));
                                                    echo "<div class='event-item' title='{$event['customer_name']} - $time'>";
                                                    echo "<small>{$event['customer_name']}</small><br>";
                                                    echo "<small class='text-muted'>$time</small>";
                                                    echo "</div>";
                                                }
                                                echo "</div>";
                                            }
                                            
                                            echo "</td>";
                                            $day_counter++;
                                        } else {
                                            echo "<td class='calendar-day empty-day'></td>";
                                        }
                                    }
                                    echo "</tr>";
                                    if ($day_counter > $days_in_month) break;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="card">
                <div class="card-body">
                    <h5>Legend</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="legend-box today-legend mr-2"></div>
                                <span>Today</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="legend-box has-events-legend mr-2"></div>
                                <span>Has Events</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Click on a day to view detailed events for that date.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">This Month</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-calendar-check text-primary"></i> Total Events:</span>
                        <strong><?php echo count($bookings); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-clock text-warning"></i> This Week:</span>
                        <strong>
                            <?php
                            $this_week_count = 0;
                            $start_of_week = date('Y-m-d', strtotime('monday this week'));
                            $end_of_week = date('Y-m-d', strtotime('sunday this week'));
                            foreach ($bookings as $booking) {
                                if ($booking['event_date'] >= $start_of_week && $booking['event_date'] <= $end_of_week) {
                                    $this_week_count++;
                                }
                            }
                            echo $this_week_count;
                            ?>
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-star text-success"></i> Available Days:</span>
                        <strong><?php echo $days_in_month - count($calendar_data); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Events</h3>
                    <div class="card-tools">
                        <a href="bookings.php" class="text-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_events)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No upcoming events</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($event['customer_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?><br>
                                            <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                        </small>
                                        <?php if ($event['venue_name']): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php
                                        $days_until = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                                        if ($days_until == 0) {
                                            echo '<span class="badge badge-danger">Today</span>';
                                        } elseif ($days_until == 1) {
                                            echo '<span class="badge badge-warning">Tomorrow</span>';
                                        } elseif ($days_until <= 7) {
                                            echo '<span class="badge badge-info">' . $days_until . ' days</span>';
                                        } else {
                                            echo '<span class="badge badge-secondary">' . $days_until . ' days</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="bookings.php?status=pending" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-clock"></i> Review Requests
                    </a>
                    <a href="services.php" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-cogs"></i> Manage Services
                    </a>
                    <a href="earnings.php" class="btn btn-success btn-block">
                        <i class="fas fa-dollar-sign"></i> View Earnings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day Events Modal -->
<div class="modal fade" id="dayEventsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Events for <span id="selectedDate"></span></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="dayEventsBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.calendar-container {
    overflow-x: auto;
}

.calendar-table {
    min-width: 700px;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    position: relative;
    padding: 5px !important;
    border: 1px solid #dee2e6 !important;
}

.calendar-day.empty-day {
    background-color: #f8f9fa;
}

.calendar-day.today {
    background-color: #e3f2fd;
}

.calendar-day.has-events {
    background-color: #fff3e0;
}

.day-number {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 5px;
}

.events {
    font-size: 10px;
}

.event-item {
    background-color: #007bff;
    color: white;
    padding: 2px 4px;
    margin-bottom: 2px;
    border-radius: 3px;
    cursor: pointer;
}

.legend-box {
    width: 20px;
    height: 20px;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    display: inline-block;
}

.today-legend {
    background-color: #e3f2fd;
}

.has-events-legend {
    background-color: #fff3e0;
}

.calendar-day:hover {
    background-color: #f0f0f0;
    cursor: pointer;
}

@media (max-width: 768px) {
    .calendar-table {
        font-size: 12px;
    }
    
    .calendar-day {
        height: 80px;
    }
    
    .day-number {
        font-size: 12px;
    }
    
    .events {
        font-size: 9px;
    }
}
</style>

<script>
$(document).ready(function() {
    // Handle day click
    $('.calendar-day.has-events').click(function() {
        const day = $(this).data('day');
        const month = <?php echo $current_month; ?>;
        const year = <?php echo $current_year; ?>;
        
        if (day) {
            loadDayEvents(day, month, year);
        }
    });
});

function loadDayEvents(day, month, year) {
    const date = new Date(year, month - 1, day);
    const dateStr = date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    $('#selectedDate').text(dateStr);
    
    // Load events via AJAX
    $.get('../includes/ajax_handler.php', {
        action: 'get_day_events',
        vendor_id: <?php echo $vendor_id; ?>,
        date: year + '-' + month.toString().padStart(2, '0') + '-' + day.toString().padStart(2, '0')
    }, function(response) {
        $('#dayEventsBody').html(response);
        $('#dayEventsModal').modal('show');
    });
}
</script>

<?php include 'layouts/footer.php'; ?>
