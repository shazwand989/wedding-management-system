<?php
/**
 * Admin Login Test - Check if admin session works properly
 */

session_start();
require_once '../includes/config.php';

echo "<h2>🔐 Admin Login & Edit Booking Test</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📊 Current Session Status</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'Not set') . "</p>";
echo "<p><strong>Logged In:</strong> " . (isLoggedIn() ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Is Admin:</strong> " . (getUserRole() === 'admin' ? '✅ Yes' : '❌ No') . "</p>";
echo "</div>";

// Test admin login
if (!isLoggedIn() || getUserRole() !== 'admin') {
    echo "<div class='alert alert-warning'>⚠️ You are not logged in as admin. The edit booking link will redirect to login page.</div>";
    
    echo "<h3>🔑 Admin Login Test</h3>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_test'])) {
        // Test admin login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_role'] = 'admin';
            echo "<div class='alert alert-success'>✅ Admin login simulated! User ID: " . $admin['id'] . "</div>";
            echo "<script>setTimeout(() => window.location.reload(), 1500);</script>";
        } else {
            echo "<div class='alert alert-danger'>❌ No admin user found in database!</div>";
        }
    }
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='login_test' class='btn btn-primary'>🔐 Simulate Admin Login</button>";
    echo "</form>";
    
} else {
    echo "<div class='alert alert-success'>✅ You are logged in as admin!</div>";
    
    echo "<h3>🔗 Edit Booking Tests</h3>";
    
    // Test booking exists
    $booking_id = 1000;
    $stmt = $pdo->prepare("SELECT b.*, u.full_name FROM bookings b LEFT JOIN users u ON b.customer_id = u.id WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>📋 Test Booking Details</h4>";
        echo "<p><strong>ID:</strong> #" . $booking['id'] . "</p>";
        echo "<p><strong>Customer:</strong> " . $booking['full_name'] . "</p>";
        echo "<p><strong>Event Date:</strong> " . $booking['event_date'] . "</p>";
        echo "<p><strong>Status:</strong> " . $booking['booking_status'] . "</p>";
        echo "</div>";
        
        // Test different edit approaches
        echo "<div class='row' style='margin: 20px 0;'>";
        
        echo "<div class='col-md-6'>";
        echo "<h4>1. Relative Link (Same as admin bookings.php)</h4>";
        echo "<a href='edit_booking.php?id=$booking_id' class='btn btn-primary btn-block' target='_blank'>";
        echo "<i class='fas fa-edit'></i> Edit Booking (Relative)";
        echo "</a>";
        echo "</div>";
        
        echo "<div class='col-md-6'>";
        echo "<h4>2. Absolute Link</h4>";
        echo "<a href='/wedding-management-system/admin/edit_booking.php?id=$booking_id' class='btn btn-success btn-block' target='_blank'>";
        echo "<i class='fas fa-edit'></i> Edit Booking (Absolute)";
        echo "</a>";
        echo "</div>";
        
        echo "</div>";
        
        echo "<h4>3. JavaScript Navigation Test</h4>";
        echo "<button class='btn btn-info' onclick='testNavigation($booking_id)'>🔧 Test JavaScript Navigation</button>";
        
        echo "<div style='margin-top: 20px;'>";
        echo "<h4>4. Simulate Admin Bookings Page Button</h4>";
        echo "<div class='btn-group'>";
        echo "<button type='button' class='btn btn-sm btn-outline-primary' onclick='alert(\"View button works!\")' title='View Details'>";
        echo "<i class='fas fa-eye'></i>";
        echo "</button>";
        echo "<a href='edit_booking.php?id=$booking_id' class='btn btn-sm btn-outline-info' title='Edit Booking' target='_blank'>";
        echo "<i class='fas fa-edit'></i>";
        echo "</a>";
        echo "<button type='button' class='btn btn-sm btn-outline-danger' onclick='alert(\"Delete button works!\")' title='Delete Booking'>";
        echo "<i class='fas fa-trash'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-danger'>❌ Booking #$booking_id not found!</div>";
    }
    
    // Test edit_booking.php file directly
    echo "<h3>📁 File Access Test</h3>";
    $edit_file = __DIR__ . '/../admin/edit_booking.php';
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>File:</strong> " . $edit_file . "</p>";
    echo "<p><strong>Exists:</strong> " . (file_exists($edit_file) ? '✅ Yes' : '❌ No') . "</p>";
    echo "<p><strong>Readable:</strong> " . (is_readable($edit_file) ? '✅ Yes' : '❌ No') . "</p>";
    if (file_exists($edit_file)) {
        echo "<p><strong>Size:</strong> " . filesize($edit_file) . " bytes</p>";
        echo "<p><strong>Modified:</strong> " . date('Y-m-d H:i:s', filemtime($edit_file)) . "</p>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Edit Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .alert { margin: 10px 0; }
        h2, h3, h4 { color: #333; }
    </style>
</head>
<body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testNavigation(bookingId) {
    console.log('Testing navigation to edit_booking.php?id=' + bookingId);
    
    // Try different navigation methods
    const methods = [
        () => window.location.href = `edit_booking.php?id=${bookingId}`,
        () => window.open(`edit_booking.php?id=${bookingId}`, '_blank'),
        () => window.location.assign(`edit_booking.php?id=${bookingId}`),
        () => document.location = `edit_booking.php?id=${bookingId}`
    ];
    
    const methodNames = [
        'window.location.href',
        'window.open (new tab)',
        'window.location.assign',
        'document.location'
    ];
    
    const choice = prompt(`Choose navigation method:\n${methodNames.map((name, i) => `${i+1}. ${name}`).join('\n')}\n\nEnter 1-4:`);
    
    if (choice >= 1 && choice <= 4) {
        console.log(`Using method: ${methodNames[choice-1]}`);
        methods[choice-1]();
    } else {
        alert('Invalid choice. Using default method.');
        methods[1](); // Default to new tab
    }
}

$(document).ready(function() {
    console.log('Admin edit test page loaded');
    
    // Test if we can access the edit page via AJAX
    <?php if (isLoggedIn() && getUserRole() === 'admin'): ?>
    $.ajax({
        url: 'edit_booking.php?id=1000',
        method: 'HEAD',
        success: function() {
            console.log('✅ edit_booking.php is accessible via AJAX');
        },
        error: function(xhr, status, error) {
            console.error('❌ edit_booking.php AJAX test failed:', error);
        }
    });
    <?php endif; ?>
});
</script>

<div style="margin-top: 40px; padding: 20px; background: white; border-radius: 5px;">
    <h4>🔧 Troubleshooting Guide</h4>
    <ol>
        <li><strong>Check Admin Login:</strong> Must be logged in as admin user</li>
        <li><strong>Verify File Permissions:</strong> edit_booking.php must be readable</li>
        <li><strong>Check Browser Console:</strong> Look for JavaScript errors</li>
        <li><strong>Test Direct URL:</strong> Try accessing edit_booking.php?id=1000 directly</li>
        <li><strong>Clear Browser Cache:</strong> Hard refresh the admin bookings page</li>
    </ol>
    
    <h4>📋 Expected Behavior</h4>
    <p>When clicking "Edit Booking" from admin/bookings.php, it should navigate to edit_booking.php with the booking ID parameter.</p>
</div>

</body>
</html>