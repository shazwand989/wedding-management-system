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
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, role, full_name, status FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Redirect based on role
                switch ($user['role']) {
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
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    
    <div class="card" style="width: 100%; max-width: 400px; margin: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-heart" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h1 style="margin: 0;">Wedding Management</h1>
            </a>
            <p style="color: #666; margin-top: 0.5rem;">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="validate-form">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn" style="width: 100%; margin-bottom: 1rem;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div style="text-align: center;">
                <p>Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register here</a></p>
                <p><a href="index.php" style="color: var(--primary-color);">← Back to Home</a></p>
            </div>
        </form>

        <!-- Demo Login Credentials -->
        <div style="margin-top: 2rem; padding: 1rem; background-color: var(--light-gray); border-radius: 5px;">
            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Demo Accounts:</h4>
            <div style="font-size: 0.9rem; line-height: 1.4;">
                <strong>Admin:</strong><br>
                Email: admin@wedding.com<br>
                Password: password<br><br>
                
                <strong>Vendor:</strong><br>
                Email: photographer@example.com<br>
                Password: password<br><br>
                
                <em>Note: Register as a customer to test the booking system</em>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
