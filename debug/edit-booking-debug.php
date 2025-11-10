<?php
/**
 * Debug Edit Booking - Shows exactly what happens during redirect
 */

define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Edit Booking Debug Trace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .debug-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #ddd; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #cce7ff; border-color: #b8daff; color: #004085; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Edit Booking Debug Trace</h1>
    
    <?php
    echo "<div class='debug-section'>";
    echo "<h3>📊 Step-by-Step Debug Analysis</h3>";
    
    // Step 1: Check login status
    echo "<h4>Step 1: Authentication Check</h4>";
    $is_logged_in = isLoggedIn();
    $user_role = getUserRole();
    
    if (!$is_logged_in) {
        echo "<div class='alert error'>❌ <strong>NOT LOGGED IN!</strong> This would redirect to login.php</div>";
        echo "<p>Session data:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } elseif ($user_role !== 'admin') {
        echo "<div class='alert error'>❌ <strong>NOT ADMIN!</strong> User role: $user_role. This would redirect to login.php</div>";
    } else {
        echo "<div class='alert success'>✅ Authentication passed - User is logged in as admin</div>";
    }
    
    // Step 2: Check booking ID
    echo "<h4>Step 2: Booking ID Validation</h4>";
    $booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    echo "<div class='info'>";
    echo "<strong>Raw GET data:</strong><br>";
    echo "GET['id']: " . ($_GET['id'] ?? 'Not set') . "<br>";
    echo "Parsed booking_id: $booking_id<br>";
    echo "Full URL: " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? 'None') . "<br>";
    echo "</div>";
    
    if ($booking_id <= 0) {
        echo "<div class='alert error'>";
        echo "❌ <strong>REDIRECT TRIGGER FOUND!</strong><br>";
        echo "Booking ID is $booking_id (must be > 0)<br>";
        echo "<strong>This is why edit_booking.php redirects back to bookings.php!</strong><br>";
        echo "</div>";
        
        echo "<h4>🔧 Possible Solutions:</h4>";
        echo "<ul>";
        echo "<li><strong>Missing ID in URL:</strong> The edit button might not be passing ?id= parameter</li>";
        echo "<li><strong>JavaScript interference:</strong> Something preventing the link from working</li>";
        echo "<li><strong>URL encoding issue:</strong> The ID might be corrupted during navigation</li>";
        echo "<li><strong>Server redirect:</strong> Another redirect happening before this page loads</li>";
        echo "</ul>";
        
    } else {
        echo "<div class='alert success'>✅ Booking ID is valid: $booking_id</div>";
        
        // Step 3: Check if booking exists
        echo "<h4>Step 3: Database Booking Check</h4>";
        try {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking) {
                echo "<div class='alert success'>✅ Booking found in database</div>";
                echo "<div class='info'>";
                echo "<strong>Booking Details:</strong><br>";
                echo "ID: " . $booking['id'] . "<br>";
                echo "Customer ID: " . $booking['customer_id'] . "<br>";
                echo "Event Date: " . $booking['event_date'] . "<br>";
                echo "Status: " . $booking['booking_status'] . "<br>";
                echo "Created: " . ($booking['created_at'] ?? 'Unknown') . "<br>";
                echo "</div>";
            } else {
                echo "<div class='alert error'>❌ Booking not found in database</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert error'>❌ Database error: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "</div>";
    
    // Test the redirect function
    echo "<div class='debug-section'>";
    echo "<h3>🔄 Redirect Function Test</h3>";
    echo "<p>The redirectTo() function is defined in includes/functions.php:</p>";
    
    if (function_exists('redirectTo')) {
        echo "<div class='alert success'>✅ redirectTo() function exists</div>";
        
        // Show what the redirect would do (without actually redirecting)
        echo "<p><strong>If booking_id <= 0, this would execute:</strong></p>";
        echo "<pre>redirectTo('bookings.php');</pre>";
        echo "<p>Which redirects to: <code>" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/bookings.php</code></p>";
        
    } else {
        echo "<div class='alert error'>❌ redirectTo() function not found</div>";
    }
    echo "</div>";
    
    // Show current request info
    echo "<div class='debug-section'>";
    echo "<h3>🌐 Request Information</h3>";
    echo "<div class='info'>";
    echo "<strong>Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "<strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "<br>";
    echo "<strong>Referer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'None') . "<br>";
    echo "<strong>Host:</strong> " . $_SERVER['HTTP_HOST'] . "<br>";
    echo "<strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "</div>";
    echo "</div>";
    
    // Quick fix test
    echo "<div class='debug-section'>";
    echo "<h3>🔧 Quick Fix Test</h3>";
    echo "<p>Try these edit links to test different approaches:</p>";
    
    // Get a test booking ID
    $stmt = $pdo->query("SELECT id FROM bookings ORDER BY id DESC LIMIT 1");
    $test_booking = $stmt->fetch();
    
    if ($test_booking) {
        $test_id = $test_booking['id'];
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Test Booking ID: $test_id</strong><br><br>";
        
        echo "1. <a href='edit_booking.php?id=$test_id' target='_blank'>Standard Link</a><br>";
        echo "2. <a href='./edit_booking.php?id=$test_id' target='_blank'>Relative Path</a><br>";
        echo "3. <a href='/wedding-management-system/admin/edit_booking.php?id=$test_id' target='_blank'>Absolute Path</a><br>";
        echo "4. <a href='https://shazwan-danial.my/wedding-management-system/admin/edit_booking.php?id=$test_id' target='_blank'>Full URL</a><br>";
        
        echo "</div>";
    } else {
        echo "<div class='alert warning'>No bookings found for testing</div>";
    }
    echo "</div>";
    ?>
    
    <div class="debug-section">
        <h3>📝 Summary & Next Steps</h3>
        
        <?php if ($booking_id <= 0): ?>
        <div class="alert error">
            <h4>🎯 ROOT CAUSE IDENTIFIED:</h4>
            <p><strong>The booking ID is not being passed correctly to edit_booking.php</strong></p>
            
            <h5>This could be caused by:</h5>
            <ul>
                <li><strong>Link generation issue:</strong> The edit button in bookings.php might not be creating the URL correctly</li>
                <li><strong>JavaScript interference:</strong> Something preventing the link click from working</li>
                <li><strong>Server-side issue:</strong> URL rewriting or redirect happening before the page loads</li>
                <li><strong>Form vs Link issue:</strong> Using the wrong method to navigate</li>
            </ul>
            
            <h5>Recommended fixes:</h5>
            <ol>
                <li>Check the exact HTML generated by the edit button in bookings.php</li>
                <li>Test with JavaScript navigation instead of HTML links</li>
                <li>Add debug logging to see what URL is actually being requested</li>
                <li>Check for any .htaccess or server redirects</li>
            </ol>
        </div>
        <?php else: ?>
        <div class="alert success">
            <h4>✅ Navigation Working!</h4>
            <p>The booking ID was passed correctly. The edit form should load normally.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>