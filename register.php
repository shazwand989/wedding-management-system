<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin':
            redirectTo('admin/dashboard.php');
            break;
        case 'customer':
            redirectTo('customer/dashboard.php');
            break;
        case 'vendor':
            redirectTo('vendor/dashboard.php');
            break;
    }
}

$error = '';
$success = '';

if ($_POST) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $role = sanitize($_POST['role']);
    
    // Validation
    if (empty($email) || empty($password) || empty($full_name) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!in_array($role, ['customer', 'vendor'])) {
        $error = 'Invalid role selected.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email address already exists.';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, role, full_name, phone, address, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                
                if ($stmt->execute([$email, $hashed_password, $role, $full_name, $phone, $address])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // If vendor, create vendor profile
                    if ($role === 'vendor') {
                        $business_name = sanitize($_POST['business_name']);
                        $service_type = sanitize($_POST['service_type']);
                        $description = sanitize($_POST['description']);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO vendors (user_id, business_name, service_type, description, status) 
                            VALUES (?, ?, ?, ?, 'pending')
                        ");
                        $stmt->execute([$user_id, $business_name, $service_type, $description]);
                        
                        $success = 'Registration successful! Your vendor account is pending approval. You will be notified once approved.';
                    } else {
                        // Auto-login for customers
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['user_name'] = $full_name;
                        
                        redirectTo('customer/dashboard.php');
                    }
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0;">
    
    <div class="card" style="width: 100%; max-width: 500px; margin: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-heart" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h1 style="margin: 0;">Wedding Management</h1>
            </a>
            <p style="color: #666; margin-top: 0.5rem;">Create your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="validate-form">
            <div class="form-group">
                <label for="role">
                    <i class="fas fa-user-tag"></i> Account Type *
                </label>
                <select id="role" name="role" class="form-control" required onchange="toggleVendorFields()">
                    <option value="">Select account type</option>
                    <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                    <option value="vendor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'vendor') ? 'selected' : ''; ?>>Vendor</option>
                </select>
            </div>

            <div class="form-group">
                <label for="full_name">
                    <i class="fas fa-user"></i> Full Name *
                </label>
                <input type="text" id="full_name" name="full_name" class="form-control" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address *
                </label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="phone">
                    <i class="fas fa-phone"></i> Phone Number
                </label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                       placeholder="Enter your phone number">
            </div>

            <div class="form-group">
                <label for="address">
                    <i class="fas fa-map-marker-alt"></i> Address
                </label>
                <textarea id="address" name="address" class="form-control" rows="3" 
                          placeholder="Enter your address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <!-- Vendor-specific fields -->
            <div id="vendor-fields" style="display: none;">
                <div class="form-group">
                    <label for="business_name">
                        <i class="fas fa-building"></i> Business Name *
                    </label>
                    <input type="text" id="business_name" name="business_name" class="form-control" 
                           value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>"
                           placeholder="Enter your business name">
                </div>

                <div class="form-group">
                    <label for="service_type">
                        <i class="fas fa-cogs"></i> Service Type *
                    </label>
                    <select id="service_type" name="service_type" class="form-control">
                        <option value="">Select service type</option>
                        <option value="photography" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'photography') ? 'selected' : ''; ?>>Photography</option>
                        <option value="catering" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'catering') ? 'selected' : ''; ?>>Catering</option>
                        <option value="decoration" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'decoration') ? 'selected' : ''; ?>>Decoration</option>
                        <option value="music" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'music') ? 'selected' : ''; ?>>Music & Entertainment</option>
                        <option value="venue" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'venue') ? 'selected' : ''; ?>>Venue</option>
                        <option value="other" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-info-circle"></i> Business Description
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="Describe your business and services"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password *
                </label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter password (min. 6 characters)">
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm Password *
                </label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                       placeholder="Confirm your password">
            </div>

            <button type="submit" class="btn" style="width: 100%; margin-bottom: 1rem;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

            <div style="text-align: center;">
                <p>Already have an account? <a href="login.php" style="color: var(--primary-color);">Sign in here</a></p>
                <p><a href="index.php" style="color: var(--primary-color);">← Back to Home</a></p>
            </div>
        </form>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function toggleVendorFields() {
            const role = document.getElementById('role').value;
            const vendorFields = document.getElementById('vendor-fields');
            const businessName = document.getElementById('business_name');
            const serviceType = document.getElementById('service_type');
            
            if (role === 'vendor') {
                vendorFields.style.display = 'block';
                businessName.setAttribute('required', '');
                serviceType.setAttribute('required', '');
            } else {
                vendorFields.style.display = 'none';
                businessName.removeAttribute('required');
                serviceType.removeAttribute('required');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleVendorFields();
        });
    </script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
