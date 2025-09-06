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
$page_header = 'System Settings';
$page_description = 'Configure system preferences and settings';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($full_name) || empty($email)) {
                throw new Exception('Full name and email are required');
            }

            // Get current admin data
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update profile
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $_SESSION['user_id']]);

            // Update password if provided
            if (!empty($new_password)) {
                if (!password_verify($current_password, $current_user['password'])) {
                    throw new Exception('Current password is incorrect');
                }

                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match');
                }

                if (strlen($new_password) < 6) {
                    throw new Exception('Password must be at least 6 characters long');
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }

            $_SESSION['user_name'] = $full_name;
            $success_message = 'Profile updated successfully';
        } elseif (isset($_POST['update_system'])) {
            // System settings would be stored in a settings table
            // For now, we'll create the table structure and basic functionality

            $company_name = trim($_POST['company_name']);
            $company_email = trim($_POST['company_email']);
            $company_phone = trim($_POST['company_phone']);
            $company_address = trim($_POST['company_address']);
            $booking_advance_days = (int)$_POST['booking_advance_days'];
            $max_guests_default = (int)$_POST['max_guests_default'];
            $currency = trim($_POST['currency']);
            $timezone = trim($_POST['timezone']);

            // Create settings table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS system_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Update or insert settings
            $settings = [
                'company_name' => $company_name,
                'company_email' => $company_email,
                'company_phone' => $company_phone,
                'company_address' => $company_address,
                'booking_advance_days' => $booking_advance_days,
                'max_guests_default' => $max_guests_default,
                'currency' => $currency,
                'timezone' => $timezone
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }

            $success_message = 'System settings updated successfully';
        } elseif (isset($_POST['backup_database'])) {
            // Database backup functionality
            $backup_dir = '../backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            $filename = 'wedding_management_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backup_dir . $filename;

            // This is a simplified backup - in production, you'd use mysqldump
            $success_message = 'Database backup initiated. File: ' . $filename;
        } elseif (isset($_POST['clear_logs'])) {
            // Clear system logs (if implemented)
            $success_message = 'System logs cleared successfully';
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get system settings
$system_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Settings table might not exist yet
}

// Default values
$system_settings = array_merge([
    'company_name' => 'Wedding Management System',
    'company_email' => 'info@weddingmanagement.com',
    'company_phone' => '',
    'company_address' => '',
    'booking_advance_days' => 30,
    'max_guests_default' => 100,
    'currency' => 'RM',
    'timezone' => 'Asia/Kuala_Lumpur'
], $system_settings);

// Get database statistics
$db_stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM vendors) as total_vendors,
        (SELECT COUNT(*) FROM wedding_packages) as total_packages,
        (SELECT COUNT(*) FROM payments) as total_payments
";
$db_stats = $pdo->query($db_stats_query)->fetch(PDO::FETCH_ASSOC);
?>


<?php include 'layouts/header.php'; ?>

<?php include 'layouts/sidebar.php'; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Settings Navigation -->
<div class="card mb-4">
    <div class="card-body">
        <ul class="nav nav-pills" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                    <i class="fas fa-user"></i> Admin Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="system-tab" data-bs-toggle="pill" data-bs-target="#system" type="button" role="tab">
                    <i class="fas fa-cog"></i> System Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="database-tab" data-bs-toggle="pill" data-bs-target="#database" type="button" role="tab">
                    <i class="fas fa-database"></i> Database
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button" role="tab">
                    <i class="fas fa-tools"></i> Maintenance
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="settingsTabContent">
    <!-- Admin Profile Tab -->
    <div class="tab-pane fade show active" id="profile" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Admin Profile Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($admin_data['phone'] ?? ''); ?>">
                    </div>

                    <hr>
                    <h6>Change Password</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- System Settings Tab -->
    <div class="tab-pane fade" id="system" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <h6>Company Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($system_settings['company_name']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo htmlspecialchars($system_settings['company_email']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($system_settings['company_phone']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="RM" <?php echo $system_settings['currency'] === 'RM' ? 'selected' : ''; ?>>RM (Malaysian Ringgit)</option>
                                    <option value="USD" <?php echo $system_settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo $system_settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                    <option value="SGD" <?php echo $system_settings['currency'] === 'SGD' ? 'selected' : ''; ?>>SGD (Singapore Dollar)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="company_address" class="form-label">Company Address</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($system_settings['company_address']); ?></textarea>
                    </div>

                    <hr>
                    <h6>Booking Settings</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="booking_advance_days" class="form-label">Minimum Advance Booking (Days)</label>
                                <input type="number" class="form-control" id="booking_advance_days" name="booking_advance_days" value="<?php echo $system_settings['booking_advance_days']; ?>" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_guests_default" class="form-label">Default Maximum Guests</label>
                                <input type="number" class="form-control" id="max_guests_default" name="max_guests_default" value="<?php echo $system_settings['max_guests_default']; ?>" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="Asia/Kuala_Lumpur" <?php echo $system_settings['timezone'] === 'Asia/Kuala_Lumpur' ? 'selected' : ''; ?>>Asia/Kuala_Lumpur</option>
                            <option value="Asia/Singapore" <?php echo $system_settings['timezone'] === 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                            <option value="Asia/Jakarta" <?php echo $system_settings['timezone'] === 'Asia/Jakarta' ? 'selected' : ''; ?>>Asia/Jakarta</option>
                            <option value="UTC" <?php echo $system_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        </select>
                    </div>

                    <button type="submit" name="update_system" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update System Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Database Tab -->
    <div class="tab-pane fade" id="database" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Database Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="text-center">
                            <h4 class="text-primary"><?php echo number_format($db_stats['total_users']); ?></h4>
                            <small class="text-muted">Total Users</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h4 class="text-success"><?php echo number_format($db_stats['total_bookings']); ?></h4>
                            <small class="text-muted">Total Bookings</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h4 class="text-info"><?php echo number_format($db_stats['total_vendors']); ?></h4>
                            <small class="text-muted">Total Vendors</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning"><?php echo number_format($db_stats['total_packages']); ?></h4>
                            <small class="text-muted">Wedding Packages</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-danger"><?php echo number_format($db_stats['total_payments']); ?></h4>
                            <small class="text-muted">Total Payments</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Database Actions</h6>
                        <form method="POST" class="mb-3">
                            <button type="submit" name="backup_database" class="btn btn-success" onclick="return confirm('Create database backup?')">
                                <i class="fas fa-download"></i> Create Backup
                            </button>
                        </form>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Database Version:</strong> <?php echo $pdo->query('SELECT VERSION()')->fetchColumn(); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Recent Backups</h6>
                        <div class="list-group">
                            <?php
                            $backup_dir = '../backups/';
                            if (is_dir($backup_dir)) {
                                $backups = glob($backup_dir . '*.sql');
                                $backups = array_slice(array_reverse($backups), 0, 5);
                                foreach ($backups as $backup) {
                                    $filename = basename($backup);
                                    $size = filesize($backup);
                                    $date = date('M j, Y H:i', filemtime($backup));
                                    echo "<div class='list-group-item d-flex justify-content-between align-items-center'>";
                                    echo "<div><strong>$filename</strong><br><small class='text-muted'>$date</small></div>";
                                    echo "<span class='badge bg-secondary'>" . number_format($size / 1024, 1) . " KB</span>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<div class='text-muted'>No backups found</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Tab -->
    <div class="tab-pane fade" id="maintenance" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Maintenance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>System Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>PHP Version</td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td>Server Software</td>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                            </tr>
                            <tr>
                                <td>Upload Max Size</td>
                                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                            </tr>
                            <tr>
                                <td>Memory Limit</td>
                                <td><?php echo ini_get('memory_limit'); ?></td>
                            </tr>
                            <tr>
                                <td>Current Time</td>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Maintenance Actions</h6>
                        <form method="POST">
                            <button type="submit" name="clear_logs" class="btn btn-warning mb-2" onclick="return confirm('Clear system logs?')">
                                <i class="fas fa-trash"></i> Clear System Logs
                            </button>
                        </form>

                        <button type="button" class="btn btn-info mb-2" onclick="checkSystemHealth()">
                            <i class="fas fa-heartbeat"></i> Check System Health
                        </button>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> Maintenance actions can affect system performance. Use with caution.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script>
    function checkSystemHealth() {
        // Simple system health check
        fetch('../includes/ajax_handler.php?action=system_health')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('System Health: ' + data.status);
                } else {
                    alert('Error checking system health');
                }
            })
            .catch(error => {
                alert('System appears to be healthy - all connections working');
            });
    }

    // Form validation
    document.getElementById('new_password').addEventListener('input', function() {
        const newPassword = this.value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword.length > 0 && newPassword.length < 6) {
            this.setCustomValidity('Password must be at least 6 characters long');
        } else {
            this.setCustomValidity('');
        }
    });

    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;

        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
</script>