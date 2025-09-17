<?php
require_once 'includes/config.php';

// Reset admin password to 'admin123'
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@wedding.com'");
    $stmt->execute([$hashed_password]);
    
    echo "✓ Admin password reset successfully!\n";
    echo "  Email: admin@wedding.com\n";
    echo "  New Password: admin123\n";
    echo "\nNow you can login at: http://localhost/wedding-management-system/login.php\n";
    
} catch (Exception $e) {
    echo "✗ Error resetting password: " . $e->getMessage() . "\n";
}
?>
