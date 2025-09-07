<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'My Services';
$page_header = 'Service Management';
$page_description = 'Manage your services, pricing, and portfolio';

$vendor_user_id = $_SESSION['user_id'];

// Get vendor information
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.full_name, u.email, u.phone, u.address 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.user_id = ?
    ");
    $stmt->execute([$vendor_user_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        redirectTo('../login.php');
    }

    $vendor_id = $vendor['id'];

    // Handle form submissions
    if ($_POST && isset($_POST['action'])) {
        if ($_POST['action'] === 'update_service') {
            $business_name = $_POST['business_name'];
            $service_type = $_POST['service_type'];
            $description = $_POST['description'];
            $price_range = $_POST['price_range'];
            
            $stmt = $pdo->prepare("
                UPDATE vendors 
                SET business_name = ?, service_type = ?, description = ?, price_range = ? 
                WHERE id = ?
            ");
            $stmt->execute([$business_name, $service_type, $description, $price_range, $vendor_id]);
            
            $success_message = "Service information updated successfully!";
            
            // Refresh vendor data
            $stmt = $pdo->prepare("
                SELECT v.*, u.full_name, u.email, u.phone, u.address 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.user_id = ?
            ");
            $stmt->execute([$vendor_user_id]);
            $vendor = $stmt->fetch();
        }
    }

    // Get service statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT bv.booking_id) as total_bookings,
            AVG(bv.agreed_price) as avg_price,
            SUM(CASE WHEN bv.status = 'confirmed' THEN bv.agreed_price ELSE 0 END) as total_earnings,
            COUNT(CASE WHEN bv.status = 'confirmed' THEN 1 END) as completed_services
        FROM booking_vendors bv
        WHERE bv.vendor_id = ?
    ");
    $stats_stmt->execute([$vendor_id]);
    $stats = $stats_stmt->fetch();

    // Get recent reviews
    $reviews_stmt = $pdo->prepare("
        SELECT r.*, u.full_name as customer_name, b.event_date
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        JOIN bookings b ON r.booking_id = b.id
        WHERE r.vendor_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $reviews_stmt->execute([$vendor_id]);
    $recent_reviews = $reviews_stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = "Error loading service information: " . $e->getMessage();
    $vendor = null;
    $stats = null;
    $recent_reviews = [];
}

// Include layout header
include 'layouts/header.php';
?>

<div class="container-fluid">

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($vendor && $vendor['status'] !== 'active'): ?>
        <!-- Account Status Notice -->
        <div class="card card-warning">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <?php if ($vendor['status'] === 'pending'): ?>
                    <h3>Account Under Review</h3>
                    <p>Your vendor account is currently being reviewed. Complete your profile to speed up the approval process.</p>
                <?php else: ?>
                    <h3>Account Inactive</h3>
                    <p>Your vendor account is currently inactive. Please contact support.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Service Statistics -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-handshake fa-2x mb-3"></i>
                    <h3><?php echo $stats['total_bookings'] ?: 0; ?></h3>
                    <p>Total Services</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['completed_services'] ?: 0; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($stats['avg_price'] ?: 0, 0); ?></h3>
                    <p>Average Price</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x mb-3"></i>
                    <h3><?php echo number_format($vendor['rating'], 1); ?>/5.0</h3>
                    <p>Rating (<?php echo $vendor['total_reviews']; ?> reviews)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Service Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Service Information
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_service">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_name">Business Name *</label>
                                    <input type="text" id="business_name" name="business_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['business_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="service_type">Service Type *</label>
                                    <select id="service_type" name="service_type" class="form-control" required>
                                        <option value="">Select Service Type</option>
                                        <option value="photography" <?php echo $vendor['service_type'] === 'photography' ? 'selected' : ''; ?>>Photography</option>
                                        <option value="catering" <?php echo $vendor['service_type'] === 'catering' ? 'selected' : ''; ?>>Catering</option>
                                        <option value="decoration" <?php echo $vendor['service_type'] === 'decoration' ? 'selected' : ''; ?>>Decoration</option>
                                        <option value="music" <?php echo $vendor['service_type'] === 'music' ? 'selected' : ''; ?>>Music/DJ</option>
                                        <option value="venue" <?php echo $vendor['service_type'] === 'venue' ? 'selected' : ''; ?>>Venue</option>
                                        <option value="other" <?php echo $vendor['service_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="price_range">Price Range</label>
                            <input type="text" id="price_range" name="price_range" class="form-control" 
                                   value="<?php echo htmlspecialchars($vendor['price_range'] ?? ''); ?>" 
                                   placeholder="e.g., RM 2000 - RM 8000">
                            <small class="form-text text-muted">Enter your typical price range for services</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Service Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="6" required 
                                      placeholder="Describe your services, experience, and what makes you unique..."><?php echo htmlspecialchars($vendor['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Service Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Portfolio Management -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-images"></i> Portfolio
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-muted mb-3">Upload images to showcase your work and attract more customers.</p>
                            
                            <!-- Upload Section -->
                            <div class="upload-area border-dashed p-4 mb-4 text-center">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Upload Portfolio Images</h5>
                                <p class="text-muted">Drag and drop images here or click to browse</p>
                                <input type="file" id="portfolio-upload" accept="image/*" multiple style="display: none;">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('portfolio-upload').click();">
                                    <i class="fas fa-plus"></i> Add Images
                                </button>
                            </div>

                            <!-- Portfolio Gallery -->
                            <div class="portfolio-gallery">
                                <?php 
                                $portfolio_images = json_decode($vendor['portfolio_images'] ?? '[]', true);
                                if (!empty($portfolio_images)): 
                                ?>
                                    <div class="row">
                                        <?php foreach ($portfolio_images as $index => $image): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card">
                                                    <img src="../<?php echo htmlspecialchars($image); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                                    <div class="card-body p-2 text-center">
                                                        <button class="btn btn-danger btn-sm" onclick="removeImage(<?php echo $index; ?>)">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                        <h5>No portfolio images yet</h5>
                                        <p class="text-muted">Add some images to showcase your work!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-md-4">
            <!-- Business Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Business Status</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Account Status:</span>
                        <span class="badge badge-<?php echo $vendor['status'] === 'active' ? 'success' : ($vendor['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($vendor['status']); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Service Type:</span>
                        <span class="badge badge-primary"><?php echo ucfirst($vendor['service_type']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Total Reviews:</span>
                        <span><strong><?php echo $vendor['total_reviews']; ?></strong></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Average Rating:</span>
                        <span>
                            <strong><?php echo number_format($vendor['rating'], 1); ?>/5.0</strong>
                            <div class="text-warning">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $vendor['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                        </span>
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
                    <a href="calendar.php" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-calendar-alt"></i> Manage Calendar
                    </a>
                    <a href="reviews.php" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-star"></i> View Reviews
                    </a>
                    <a href="profile.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>

            <!-- Recent Reviews -->
            <?php if (!empty($recent_reviews)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Reviews</h3>
                    <div class="card-tools">
                        <a href="reviews.php" class="text-primary">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php foreach ($recent_reviews as $review): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                <div class="text-warning">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="text-muted small mb-1">"<?php echo htmlspecialchars(substr($review['comment'], 0, 100)); ?>..."</p>
                            <?php endif; ?>
                            <small class="text-muted">
                                Event: <?php echo date('M j, Y', strtotime($review['event_date'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-dashed {
    border: 2px dashed #dee2e6 !important;
    border-radius: 8px;
}

.upload-area:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.portfolio-gallery .card {
    transition: transform 0.2s;
}

.portfolio-gallery .card:hover {
    transform: translateY(-2px);
}
</style>

<script>
function removeImage(index) {
    Swal.fire({
        title: 'Remove Image?',
        text: 'Are you sure you want to remove this image from your portfolio?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX call to remove image
            $.post('../includes/ajax_handler.php', {
                action: 'remove_portfolio_image',
                vendor_id: <?php echo $vendor_id; ?>,
                image_index: index
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', 'Failed to remove image', 'error');
                }
            }, 'json');
        }
    });
}

document.getElementById('portfolio-upload').addEventListener('change', function(e) {
    const files = e.target.files;
    if (files.length > 0) {
        const formData = new FormData();
        formData.append('action', 'upload_portfolio_images');
        formData.append('vendor_id', <?php echo $vendor_id; ?>);
        
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        // Show loading
        Swal.fire({
            title: 'Uploading...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../includes/ajax_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Success', 'Images uploaded successfully!', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.message || 'Failed to upload images', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to upload images', 'error');
            }
        });
    }
});
</script>

<?php include 'layouts/footer.php'; ?>
