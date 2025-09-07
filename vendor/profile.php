<?php
define('VENDOR_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is vendor
if (!isLoggedIn() || getUserRole() !== 'vendor') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Profile';
$page_header = 'Vendor Profile';
$page_description = 'Manage your account and business information';

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
        
        if ($_POST['action'] === 'update_profile') {
            // Update user information
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, address = ? 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $phone, $address, $vendor_user_id]);
            
            // Update vendor information
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
            
            $success_message = "Profile updated successfully!";
            
            // Refresh data
            $stmt = $pdo->prepare("
                SELECT v.*, u.full_name, u.email, u.phone, u.address 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.user_id = ?
            ");
            $stmt->execute([$vendor_user_id]);
            $vendor = $stmt->fetch();
        }
        
        if ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$vendor_user_id]);
            $user_data = $stmt->fetch();
            
            if (password_verify($current_password, $user_data['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $vendor_user_id]);
                        
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Password must be at least 6 characters long.";
                    }
                } else {
                    $error_message = "New passwords do not match.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }

    // Get account statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT bv.booking_id) as total_bookings,
            COUNT(CASE WHEN bv.status = 'confirmed' THEN 1 END) as confirmed_bookings,
            SUM(CASE WHEN bv.status = 'confirmed' THEN bv.agreed_price ELSE 0 END) as total_earnings,
            COUNT(DISTINCT r.id) as total_reviews
        FROM booking_vendors bv
        LEFT JOIN reviews r ON bv.vendor_id = r.vendor_id
        WHERE bv.vendor_id = ?
    ");
    $stats_stmt->execute([$vendor_id]);
    $stats = $stats_stmt->fetch();

} catch (PDOException $e) {
    $error_message = "Error loading profile: " . $e->getMessage();
    $vendor = null;
    $stats = null;
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

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> Personal Information
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>" 
                                           placeholder="+60123456789">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>" 
                                           placeholder="Full address">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5>Business Information</h5>

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
                        </div>

                        <div class="form-group">
                            <label for="description">Business Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="6" required 
                                      placeholder="Describe your services, experience, and what makes you unique..."><?php echo htmlspecialchars($vendor['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lock"></i> Change Password
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="new_password">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" 
                                           minlength="6" required>
                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           minlength="6" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Account Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Status</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($vendor['business_name']); ?></h4>
                    <p class="text-muted"><?php echo ucfirst($vendor['service_type']); ?> Service</p>
                    
                    <div class="status-badge mb-3">
                        <span class="badge badge-lg badge-<?php 
                            echo $vendor['status'] === 'active' ? 'success' : 
                                ($vendor['status'] === 'pending' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($vendor['status']); ?>
                        </span>
                    </div>

                    <div class="rating-display mb-3">
                        <div class="text-warning">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $vendor['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <small class="text-muted">
                            <?php echo number_format($vendor['rating'], 1); ?>/5.0 
                            (<?php echo $vendor['total_reviews']; ?> reviews)
                        </small>
                    </div>

                    <?php if ($vendor['status'] === 'pending'): ?>
                        <div class="alert alert-warning">
                            <small>Your account is under review. Complete your profile to speed up the approval process.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-calendar-check text-primary"></i> Total Bookings:</span>
                        <strong><?php echo $stats['total_bookings'] ?: 0; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-check-circle text-success"></i> Confirmed:</span>
                        <strong><?php echo $stats['confirmed_bookings'] ?: 0; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-dollar-sign text-info"></i> Total Earnings:</span>
                        <strong>RM <?php echo number_format($stats['total_earnings'] ?: 0, 0); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-star text-warning"></i> Reviews:</span>
                        <strong><?php echo $stats['total_reviews'] ?: 0; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Actions</h3>
                </div>
                <div class="card-body">
                    <a href="services.php" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-cogs"></i> Manage Services
                    </a>
                    <a href="bookings.php" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-calendar-check"></i> View Bookings
                    </a>
                    <a href="reviews.php" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-star"></i> Customer Reviews
                    </a>
                    <button class="btn btn-danger btn-block" onclick="deactivateAccount()">
                        <i class="fas fa-user-times"></i> Deactivate Account
                    </button>
                </div>
            </div>

            <!-- Profile Completeness -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Completeness</h3>
                </div>
                <div class="card-body">
                    <?php
                    $completeness = 0;
                    $total_fields = 7;
                    
                    if (!empty($vendor['full_name'])) $completeness++;
                    if (!empty($vendor['email'])) $completeness++;
                    if (!empty($vendor['phone'])) $completeness++;
                    if (!empty($vendor['business_name'])) $completeness++;
                    if (!empty($vendor['service_type'])) $completeness++;
                    if (!empty($vendor['description'])) $completeness++;
                    if (!empty($vendor['price_range'])) $completeness++;
                    
                    $percentage = round(($completeness / $total_fields) * 100);
                    ?>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar bg-<?php echo $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger'); ?>" 
                             style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    
                    <p class="text-center">
                        <strong><?php echo $percentage; ?>% Complete</strong>
                    </p>
                    
                    <?php if ($percentage < 100): ?>
                        <div class="missing-fields">
                            <small class="text-muted">Missing fields:</small>
                            <ul class="list-unstyled">
                                <?php if (empty($vendor['phone'])): ?><li><small>• Phone number</small></li><?php endif; ?>
                                <?php if (empty($vendor['price_range'])): ?><li><small>• Price range</small></li><?php endif; ?>
                                <?php if (empty($vendor['description'])): ?><li><small>• Description</small></li><?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

.rating-display {
    font-size: 1.2rem;
}

.missing-fields ul {
    margin-bottom: 0;
}

.missing-fields small {
    color: #dc3545;
}

.status-badge {
    margin: 10px 0;
}

.card-body .d-flex {
    border-bottom: 1px solid #f8f9fa;
    padding-bottom: 8px;
}

.card-body .d-flex:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
</style>

<script>
$(document).ready(function() {
    // Password confirmation validation
    $('#passwordForm').on('submit', function(e) {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            Swal.fire('Error', 'Passwords do not match!', 'error');
            return false;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            Swal.fire('Error', 'Password must be at least 6 characters long!', 'error');
            return false;
        }
    });
    
    // Real-time password confirmation
    $('#confirm_password').on('keyup', function() {
        const newPassword = $('#new_password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword.length > 0) {
            if (newPassword === confirmPassword) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
});

function deactivateAccount() {
    Swal.fire({
        title: 'Deactivate Account?',
        text: 'This will temporarily disable your account. You can reactivate it by contacting support.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, deactivate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX call to deactivate account
            $.post('../includes/ajax_handler.php', {
                action: 'deactivate_vendor_account',
                vendor_id: <?php echo $vendor_id; ?>
            }, function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Deactivated', 'Your account has been deactivated.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.message || 'Failed to deactivate account', 'error');
                }
            });
        }
    });
}
</script>

<?php include 'layouts/footer.php'; ?>
