<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'My Profile';
$breadcrumbs = [
    ['title' => 'My Profile']
];

$customer_id = $_SESSION['user_id'];

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $date_of_birth = $_POST['date_of_birth'] ?? null;
        $gender = $_POST['gender'] ?? '';
        $address = $_POST['address'] ?? '';
        
        $errors = [];
        
        // Validation
        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($email)) $errors[] = "Email is required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
        
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $customer_id]);
        if ($stmt->fetch()) {
            $errors[] = "Email is already taken by another user.";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, address = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $email, $phone, $date_of_birth ?: null, $gender, $address, $customer_id]);
                
                // Update session data
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                
                $success_message = "Profile updated successfully!";
            } catch (PDOException $e) {
                $errors[] = "Error updating profile: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($current_password)) $errors[] = "Current password is required.";
        if (empty($new_password)) $errors[] = "New password is required.";
        if (strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters.";
        if ($new_password !== $confirm_password) $errors[] = "Password confirmation does not match.";
        
        if (empty($errors)) {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$customer_id]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed_password, $customer_id]);
                    
                    $success_message = "Password changed successfully!";
                } else {
                    $errors[] = "Current password is incorrect.";
                }
            } catch (PDOException $e) {
                $errors[] = "Error changing password: " . $e->getMessage();
            }
        }
    }
}

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch();
    
    // Get customer statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(total_amount) as total_spent,
            MAX(created_at) as last_booking_date
        FROM bookings 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $stats = $stmt->fetch();
    
    // Ensure stats have default values
    $stats = [
        'total_bookings' => (int)($stats['total_bookings'] ?? 0),
        'completed_bookings' => (int)($stats['completed_bookings'] ?? 0),
        'pending_bookings' => (int)($stats['pending_bookings'] ?? 0),
        'total_spent' => (float)($stats['total_spent'] ?? 0),
        'last_booking_date' => $stats['last_booking_date']
    ];

} catch (PDOException $e) {
    $error_message = "Error loading profile: " . $e->getMessage();
    $user = null;
    $stats = ['total_bookings' => 0, 'completed_bookings' => 0, 'pending_bookings' => 0, 'total_spent' => 0, 'last_booking_date' => null];
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div class="text-center">
                        <div class="profile-user-img img-circle elevation-2 d-inline-flex align-items-center justify-content-center bg-primary text-white" 
                             style="width: 80px; height: 80px; font-size: 24px;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <h3 class="profile-username text-center"><?php echo htmlspecialchars($user['full_name'] ?? 'Customer'); ?></h3>
                    <p class="text-muted text-center"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    
                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <strong><i class="fas fa-phone mr-1"></i> Phone</strong>
                            <span class="float-right"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></span>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="fas fa-calendar mr-1"></i> Date of Birth</strong>
                            <span class="float-right">
                                <?php echo $user['date_of_birth'] ? date('M j, Y', strtotime($user['date_of_birth'])) : 'Not provided'; ?>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="fas fa-venus-mars mr-1"></i> Gender</strong>
                            <span class="float-right"><?php echo $user['gender'] ? ucfirst($user['gender']) : 'Not specified'; ?></span>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="fas fa-clock mr-1"></i> Member Since</strong>
                            <span class="float-right"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">My Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <strong class="d-block h4 text-primary"><?php echo $stats['total_bookings']; ?></strong>
                                <span class="d-block text-muted">Total Bookings</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <strong class="d-block h4 text-success"><?php echo $stats['completed_bookings']; ?></strong>
                            <span class="d-block text-muted">Completed</span>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <strong class="d-block h4 text-warning"><?php echo $stats['pending_bookings']; ?></strong>
                                <span class="d-block text-muted">Pending</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <strong class="d-block h4 text-info">RM <?php echo number_format($stats['total_spent'], 0); ?></strong>
                            <span class="d-block text-muted">Total Spent</span>
                        </div>
                    </div>
                    <?php if ($stats['last_booking_date']): ?>
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                Last booking: <?php echo date('M j, Y', strtotime($stats['last_booking_date'])); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Forms -->
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Personal Information</h3>
                </div>
                <form method="POST" action="">
                    <div class="card-body">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                           value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <form method="POST" action="">
                    <div class="card-body">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="new_password">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="6" required>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Actions -->
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Account Actions</h3>
                </div>
                <div class="card-body">
                    <h5>Need Help?</h5>
                    <p class="text-muted">If you have any questions or need assistance with your account, our support team is here to help.</p>
                    
                    <div class="btn-group">
                        <a href="mailto:support@weddingmanagement.com" class="btn btn-info">
                            <i class="fas fa-envelope"></i> Contact Support
                        </a>
                        <a href="../includes/logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="text-danger">Danger Zone</h5>
                    <p class="text-muted">Permanently delete your account and all associated data. This action cannot be undone.</p>
                    <button class="btn btn-danger" onclick="confirmAccountDeletion()">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

function confirmAccountDeletion() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.')) {
        if (confirm('This is your final warning. Are you absolutely sure you want to delete your account?')) {
            alert('Account deletion feature will be implemented. Please contact support for now.');
        }
    }
}
</script>

<?php include 'layouts/footer.php'; ?>
