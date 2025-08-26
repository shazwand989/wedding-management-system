<?php
// Database installation script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if ($_POST) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'wedding_management';
    
    try {
        // Connect to MySQL without selecting database
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute SQL file
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        
        $success = "Database installed successfully! You can now <a href='index.php' style='color: var(--primary-color);'>visit your website</a>.";
        
    } catch (PDOException $e) {
        $error = "Database installation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Wedding Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    
    <div class="card" style="width: 100%; max-width: 500px; margin: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-heart" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
            <h1 style="margin: 0; color: var(--primary-color);">Wedding Management System</h1>
            <p style="color: #666; margin-top: 0.5rem;">Database Installation</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            
            <div style="margin-top: 2rem; padding: 1rem; background-color: var(--light-gray); border-radius: 5px;">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Default Login Credentials:</h4>
                <div style="font-size: 0.9rem; line-height: 1.6;">
                    <strong>Admin Account:</strong><br>
                    Email: admin@wedding.com<br>
                    Password: password<br><br>
                    
                    <strong>Sample Vendor Accounts:</strong><br>
                    Email: photographer@example.com<br>
                    Email: caterer@example.com<br>
                    Email: decorator@example.com<br>
                    Password: password (for all vendor accounts)<br><br>
                    
                    <em>Note: Please change these passwords after installation for security.</em>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" class="validate-form">
                <div class="form-group">
                    <label for="db_host">
                        <i class="fas fa-server"></i> Database Host
                    </label>
                    <input type="text" id="db_host" name="db_host" class="form-control" required
                           value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : 'localhost'; ?>"
                           placeholder="localhost">
                </div>

                <div class="form-group">
                    <label for="db_user">
                        <i class="fas fa-user"></i> Database Username
                    </label>
                    <input type="text" id="db_user" name="db_user" class="form-control" required
                           value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : 'root'; ?>"
                           placeholder="root">
                </div>

                <div class="form-group">
                    <label for="db_pass">
                        <i class="fas fa-lock"></i> Database Password
                    </label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control"
                           placeholder="Enter database password (leave empty if none)">
                </div>

                <div class="form-group">
                    <label for="db_name">
                        <i class="fas fa-database"></i> Database Name
                    </label>
                    <input type="text" id="db_name" name="db_name" class="form-control" required
                           value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : 'wedding_management'; ?>"
                           placeholder="wedding_management">
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-bottom: 1rem;">
                    <i class="fas fa-download"></i> Install Database
                </button>
            </form>

            <div style="margin-top: 2rem; padding: 1rem; background-color: var(--light-gray); border-radius: 5px;">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Requirements:</h4>
                <ul style="font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>PHP 7.4 or higher</li>
                    <li>MySQL 5.7 or higher</li>
                    <li>PDO MySQL extension enabled</li>
                    <li>Web server (Apache/Nginx)</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
