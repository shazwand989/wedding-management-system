<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Reviews';
$page_header = 'Customer Reviews';
$page_description = 'View and manage customer feedback';

$vendor_user_id = $_SESSION['user_id'];

// Get vendor information
try {
    $stmt = $pdo->prepare("SELECT id, rating, total_reviews FROM vendors WHERE user_id = ?");
    $stmt->execute([$vendor_user_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        redirectTo('../login.php');
    }

    $vendor_id = $vendor['id'];

    // Get filter parameters
    $rating_filter = $_GET['rating'] ?? 'all';
    $sort_filter = $_GET['sort'] ?? 'newest';

    // Build query conditions
    $where_conditions = ["r.vendor_id = ?"];
    $params = [$vendor_id];

    if ($rating_filter !== 'all') {
        $where_conditions[] = "r.rating = ?";
        $params[] = $rating_filter;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Determine sort order
    $order_clause = "ORDER BY ";
    switch ($sort_filter) {
        case 'oldest':
            $order_clause .= "r.created_at ASC";
            break;
        case 'highest':
            $order_clause .= "r.rating DESC, r.created_at DESC";
            break;
        case 'lowest':
            $order_clause .= "r.rating ASC, r.created_at DESC";
            break;
        default: // newest
            $order_clause .= "r.created_at DESC";
    }

    // Get reviews
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name as customer_name, b.event_date
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        JOIN bookings b ON r.booking_id = b.id
        WHERE $where_clause
        $order_clause
    ");
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    // Get rating statistics
    $rating_stats_stmt = $pdo->prepare("
        SELECT 
            rating,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM reviews WHERE vendor_id = ?)), 1) as percentage
        FROM reviews 
        WHERE vendor_id = ?
        GROUP BY rating
        ORDER BY rating DESC
    ");
    $rating_stats_stmt->execute([$vendor_id, $vendor_id]);
    $rating_stats = $rating_stats_stmt->fetchAll();

    // Get monthly review trends
    $monthly_reviews_stmt = $pdo->prepare("
        SELECT 
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            COUNT(*) as review_count,
            AVG(rating) as avg_rating
        FROM reviews 
        WHERE vendor_id = ?
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");
    $monthly_reviews_stmt->execute([$vendor_id]);
    $monthly_reviews_raw = $monthly_reviews_stmt->fetchAll();
    
    // Add month names to results
    $month_names = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    $monthly_reviews = [];
    foreach ($monthly_reviews_raw as $row) {
        $row['month_name'] = $month_names[$row['month']];
        $monthly_reviews[] = $row;
    }

    // Calculate overall statistics
    $total_reviews = count($reviews);
    $average_rating = $vendor['rating'];

    // Create rating distribution array
    $rating_distribution = [];
    for ($i = 5; $i >= 1; $i--) {
        $rating_distribution[$i] = ['count' => 0, 'percentage' => 0];
    }
    foreach ($rating_stats as $stat) {
        $rating_distribution[$stat['rating']] = [
            'count' => $stat['count'],
            'percentage' => $stat['percentage']
        ];
    }

} catch (PDOException $e) {
    $error_message = "Error loading reviews: " . $e->getMessage();
    $reviews = [];
    $rating_stats = [];
    $monthly_reviews = [];
    $rating_distribution = [];
    $total_reviews = 0;
    $average_rating = 0;
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

    <!-- Overview Statistics -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x mb-3"></i>
                    <h3><?php echo number_format($average_rating, 1); ?></h3>
                    <p>Average Rating</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-2x mb-3"></i>
                    <h3><?php echo $total_reviews; ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-thumbs-up fa-2x mb-3"></i>
                    <h3><?php 
                        $positive_count = 0;
                        $positive_count += isset($rating_distribution[5]) ? $rating_distribution[5]['count'] : 0;
                        $positive_count += isset($rating_distribution[4]) ? $rating_distribution[4]['count'] : 0;
                        echo $positive_count; 
                    ?></h3>
                    <p>Positive Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                    <h3>
                        <?php 
                        $this_month_reviews = 0;
                        $current_month = date('n');
                        $current_year = date('Y');
                        foreach ($monthly_reviews as $month_data) {
                            if ($month_data['month'] == $current_month && $month_data['year'] == $current_year) {
                                $this_month_reviews = $month_data['review_count'];
                                break;
                            }
                        }
                        echo $this_month_reviews;
                        ?>
                    </h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Reviews List -->
        <div class="col-md-8">
            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filter Reviews</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Rating:</label>
                                    <select name="rating" class="form-control" onchange="this.form.submit()">
                                        <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                                        <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                                        <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                                        <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                                        <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                                        <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 Star</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sort By:</label>
                                    <select name="sort" class="form-control" onchange="this.form.submit()">
                                        <option value="newest" <?php echo $sort_filter === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                        <option value="oldest" <?php echo $sort_filter === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                        <option value="highest" <?php echo $sort_filter === 'highest' ? 'selected' : ''; ?>>Highest Rating</option>
                                        <option value="lowest" <?php echo $sort_filter === 'lowest' ? 'selected' : ''; ?>>Lowest Rating</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <a href="reviews.php" class="btn btn-secondary">Reset Filters</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reviews -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer Reviews (<?php echo count($reviews); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star-o fa-4x text-muted mb-3"></i>
                            <h4>No Reviews Found</h4>
                            <p class="text-muted">No reviews match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item border rounded p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($review['customer_name']); ?></h5>
                                        <div class="text-warning mb-2">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                            <span class="ml-2 text-muted"><?php echo $review['rating']; ?>/5</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                        </small><br>
                                        <small class="text-muted">
                                            Event: <?php echo date('M j, Y', strtotime($review['event_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if ($review['comment']): ?>
                                    <div class="review-comment">
                                        <blockquote class="blockquote">
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        </blockquote>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted font-style-italic">No written comment provided.</p>
                                <?php endif; ?>
                                
                                <div class="review-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> Reviewed <?php echo timeAgo($review['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Rating Distribution -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rating Distribution</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($rating_distribution as $rating => $data): ?>
                        <div class="d-flex align-items-center mb-3">
                            <span class="rating-label mr-2" style="min-width: 60px;">
                                <?php echo $rating; ?> star<?php echo $rating > 1 ? 's' : ''; ?>
                            </span>
                            <div class="progress flex-grow-1 mr-2" style="height: 20px;">
                                <div class="progress-bar bg-<?php 
                                    echo $rating >= 4 ? 'success' : ($rating >= 3 ? 'warning' : 'danger'); 
                                ?>" 
                                style="width: <?php echo $data['percentage']; ?>%"></div>
                            </div>
                            <span class="rating-count" style="min-width: 40px;">
                                <?php echo $data['count']; ?> (<?php echo $data['percentage']; ?>%)
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Review Trends -->
            <?php if (!empty($monthly_reviews)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Review Trends</h3>
                </div>
                <div class="card-body">
                    <canvas id="reviewTrendsChart" style="height: 200px;"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Review Insights -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Review Insights</h3>
                </div>
                <div class="card-body">
                    <div class="insight-item mb-3">
                        <h6>Most Common Rating</h6>
                        <p class="text-primary">
                            <?php 
                            $most_common = 0;
                            $max_count = 0;
                            foreach ($rating_distribution as $rating => $data) {
                                if ($data['count'] > $max_count) {
                                    $max_count = $data['count'];
                                    $most_common = $rating;
                                }
                            }
                            echo $most_common; 
                            ?> stars (<?php echo $max_count; ?> reviews)
                        </p>
                    </div>
                    
                    <div class="insight-item mb-3">
                        <h6>Customer Satisfaction</h6>
                        <p class="<?php echo $average_rating >= 4 ? 'text-success' : ($average_rating >= 3 ? 'text-warning' : 'text-danger'); ?>">
                            <?php 
                            if ($average_rating >= 4.5) echo "Excellent";
                            elseif ($average_rating >= 4) echo "Very Good";
                            elseif ($average_rating >= 3) echo "Good";
                            elseif ($average_rating >= 2) echo "Fair";
                            else echo "Needs Improvement";
                            ?>
                        </p>
                    </div>
                    
                    <div class="insight-item">
                        <h6>Response Rate</h6>
                        <p class="text-info">
                            <?php 
                            $response_rate = $total_reviews > 0 ? 
                                round((array_sum(array_column($rating_distribution, 'count')) / $vendor['total_reviews']) * 100, 1) : 0;
                            echo $response_rate; 
                            ?>% of customers leave reviews
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="services.php" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-cogs"></i> Improve Services
                    </a>
                    <a href="bookings.php" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-calendar-check"></i> View Bookings
                    </a>
                    <button class="btn btn-success btn-block" onclick="shareReviews()">
                        <i class="fas fa-share-alt"></i> Share Reviews
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.review-item {
    transition: box-shadow 0.3s ease;
}

.review-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.review-comment {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin: 15px 0;
    border-radius: 0 5px 5px 0;
}

.rating-label {
    font-size: 14px;
    font-weight: 500;
}

.rating-count {
    font-size: 12px;
    font-weight: 500;
}

.insight-item h6 {
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.insight-item p {
    font-weight: 600;
    margin-bottom: 0;
}
</style>

<?php if (!empty($monthly_reviews)): ?>
<script>
$(document).ready(function() {
    // Review trends chart
    const ctx = document.getElementById('reviewTrendsChart').getContext('2d');
    const reviewTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_reviews, 'month_name')); ?>,
            datasets: [{
                label: 'Reviews',
                data: <?php echo json_encode(array_column($monthly_reviews, 'review_count')); ?>,
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Avg Rating',
                data: <?php echo json_encode(array_map('floatval', array_column($monthly_reviews, 'avg_rating'))); ?>,
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: false,
                yAxisID: 'y1'
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
                        text: 'Number of Reviews'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    min: 1,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Average Rating'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<script>
function shareReviews() {
    const url = window.location.origin + '/vendor/reviews.php?vendor_id=<?php echo $vendor_id; ?>';
    
    if (navigator.share) {
        navigator.share({
            title: 'Check out my customer reviews!',
            text: 'See what my customers are saying about my services.',
            url: url
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire('Success', 'Review link copied to clipboard!', 'success');
        });
    }
}

function timeAgo(timestamp) {
    const now = new Date();
    const reviewDate = new Date(timestamp);
    const diffInSeconds = Math.floor((now - reviewDate) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    
    return reviewDate.toLocaleDateString();
}
</script>

<?php
function timeAgo($timestamp) {
    $now = time();
    $reviewTime = strtotime($timestamp);
    $diff = $now - $reviewTime;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $reviewTime);
}

include 'layouts/footer.php'; 
?>
