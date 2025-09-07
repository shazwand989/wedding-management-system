<?php
require_once 'includes/config.php';

// Check for demo parameter
$demoType = isset($_GET['demo']) ? $_GET['demo'] : '';

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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            background: #f8f9fa;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hearts" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><text x="10" y="15" text-anchor="middle" fill="rgba(255,255,255,0.1)" font-size="12">♥</text></pattern></defs><rect width="100" height="100" fill="url(%23hearts)"/></svg>');
            opacity: 0.3;
        }
        
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: white;
        }
        
        .demo-section {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            max-width: 400px;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 400px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                flex: none;
                min-height: 40vh;
                padding: 2rem;
            }
            
            .login-right {
                flex: none;
                min-height: 60vh;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Demo Section -->
        <div class="login-left">
            <div class="demo-section">
                <div style="margin-bottom: 3rem;">
                    <i class="fas fa-heart" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h1 style="margin: 0; font-size: 2.5rem; font-weight: bold;">Wedding Management</h1>
                    <p style="margin-top: 1rem; font-size: 1.2rem; opacity: 0.9;">Your Perfect Wedding Starts Here</p>
                </div>

                <!-- Quick Demo Login -->
                <div style="margin-top: 3rem;">
                    <h3 style="margin-bottom: 2rem; font-size: 1.8rem;">
                        <i class="fas fa-rocket"></i> Try Demo Accounts
                    </h3>
                    <div style="display: grid; gap: 1rem;">
                        <button type="button" class="btn demo-login-btn" 
                                data-email="admin@wedding.com" 
                                data-password="password"
                                style="background: rgba(220, 53, 69, 0.9); color: white; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; backdrop-filter: blur(10px);">
                            <i class="fas fa-crown"></i> Login as Admin
                            <small style="display: block; font-size: 0.8rem; opacity: 0.9; margin-top: 0.3rem;">Manage system, bookings & vendors</small>
                        </button>

                        <button type="button" class="btn demo-login-btn" 
                                data-email="photographer@example.com" 
                                data-password="password"
                                style="background: rgba(40, 167, 69, 0.9); color: white; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; backdrop-filter: blur(10px);">
                            <i class="fas fa-camera"></i> Login as Vendor
                            <small style="display: block; font-size: 0.8rem; opacity: 0.9; margin-top: 0.3rem;">View bookings & manage services</small>
                        </button>

                        <button type="button" class="btn demo-login-btn" 
                                data-email="customer@demo.com" 
                                data-password="password"
                                style="background: rgba(0, 123, 255, 0.9); color: white; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; backdrop-filter: blur(10px);">
                            <i class="fas fa-heart"></i> Login as Customer
                            <small style="display: block; font-size: 0.8rem; opacity: 0.9; margin-top: 0.3rem;">Book wedding packages & services</small>
                        </button>
                    </div>

                    <div style="margin-top: 2rem; font-size: 0.9rem; opacity: 0.8;">
                        <i class="fas fa-info-circle"></i> Click any button to instantly login with demo credentials
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-form-container">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h2 style="color: var(--primary-color); margin-bottom: 0.5rem;">Welcome Back</h2>
                    <p style="color: #666; font-size: 1.1rem;">Sign in to your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" class="validate-form">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="email" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">
                            <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--primary-color);"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="Enter your email"
                               style="padding: 0.75rem; border-radius: 8px; border: 2px solid #e1e5e9; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label for="password" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">
                            <i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--primary-color);"></i> Password
                        </label>
                        <input type="password" id="password" name="password" class="form-control" required
                               placeholder="Enter your password"
                               style="padding: 0.75rem; border-radius: 8px; border: 2px solid #e1e5e9; font-size: 1rem;">
                    </div>

                    <button type="submit" class="btn" style="width: 100%; margin-bottom: 2rem; padding: 0.875rem; font-size: 1.1rem; border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>

                    <div style="text-align: center;">
                        <p style="margin-bottom: 1rem;">Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 500;">Register here</a></p>
                        <p><a href="index.php" style="color: var(--primary-color); font-weight: 500;"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
    // Demo login functionality
    document.addEventListener('DOMContentLoaded', function() {
        const demoButtons = document.querySelectorAll('.demo-login-btn');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginForm = document.querySelector('.validate-form');

        // Check for demo parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const demoType = urlParams.get('demo');
        
        // Auto-trigger demo login if demo parameter is present
        if (demoType) {
            const demoCredentials = {
                'admin': 'admin@wedding.com',
                'vendor': 'photographer@example.com', 
                'customer': 'customer@demo.com'
            };
            
            if (demoCredentials[demoType]) {
                setTimeout(() => {
                    emailInput.value = demoCredentials[demoType];
                    passwordInput.value = 'password';
                    
                    // Highlight the corresponding demo button
                    const targetButton = document.querySelector(`[data-email="${demoCredentials[demoType]}"]`);
                    if (targetButton) {
                        targetButton.style.transform = 'scale(1.05)';
                        targetButton.style.boxShadow = '0 10px 30px rgba(255,255,255,0.3)';
                        targetButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Auto-logging in...';
                        targetButton.disabled = true;
                    }
                    
                    // Auto-submit after delay
                    setTimeout(() => {
                        loginForm.submit();
                    }, 1000);
                }, 500);
            }
        }

        demoButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Add loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
                this.disabled = true;

                // Get credentials from data attributes
                const email = this.getAttribute('data-email');
                const password = this.getAttribute('data-password');

                // Fill form fields
                emailInput.value = email;
                passwordInput.value = password;

                // Submit form after small delay for visual feedback
                setTimeout(() => {
                    loginForm.submit();
                }, 500);
            });

            // Add hover effects
            button.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                    this.style.boxShadow = '0 8px 25px rgba(255,255,255,0.2)';
                }
            });

            button.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = 'none';
                }
            });
        });

        // Add focus effects to form inputs
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = 'var(--primary-color)';
                this.style.boxShadow = '0 0 0 3px rgba(212, 175, 55, 0.1)';
            });

            input.addEventListener('blur', function() {
                this.style.borderColor = '#e1e5e9';
                this.style.boxShadow = 'none';
            });
        });
    });
    </script>
</body>
</html>
