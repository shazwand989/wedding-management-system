<?php
require_once 'includes/config.php';

echo "=== Admin Accounts Check ===\n";

try {
    $stmt = $pdo->query("SELECT id, email, role FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll();
    
    if (empty($admins)) {
        echo "✗ No admin accounts found!\n";
        echo "  You need to create an admin account first.\n";
    } else {
        echo "✓ Found admin accounts:\n";
        foreach ($admins as $admin) {
            echo "  - Email: " . $admin['email'] . " (ID: " . $admin['id'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error checking admin accounts: " . $e->getMessage() . "\n";
}

// Also check all users
echo "\n=== All Users ===\n";
try {
    $stmt = $pdo->query("SELECT id, email, role FROM users ORDER BY role, email");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "  - " . $user['email'] . " (" . $user['role'] . ")\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking users: " . $e->getMessage() . "\n";
}
?>
