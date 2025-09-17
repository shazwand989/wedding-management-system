<?php
session_start();
require_once '../includes/config.php';

echo "=== Admin Login Check ===\n";

if (isset($_SESSION['user_id'])) {
    echo "✓ You are logged in as user ID: " . $_SESSION['user_id'] . "\n";
    echo "✓ Your role is: " . ($_SESSION['user_role'] ?? 'not set') . "\n";
    
    if ($_SESSION['user_role'] === 'admin') {
        echo "✓ You have admin access\n";
        echo "✓ You can access edit_booking.php?id=1\n";
    } else {
        echo "✗ You don't have admin role (current: " . $_SESSION['user_role'] . ")\n";
    }
} else {
    echo "✗ You are not logged in\n";
    echo "  Please login first at: login.php\n";
    echo "  Then access: admin/edit_booking.php?id=1\n";
}

echo "\n=== Session Data ===\n";
print_r($_SESSION);
?>
