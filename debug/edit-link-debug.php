<?php
/**
 * Edit Booking Link Debug - Trace exactly what happens
 */

define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Simulate admin login
if (!isLoggedIn() || getUserRole() !== 'admin') {
    $_SESSION['user_id'] = 1; // Simulate admin login
    $_SESSION['user_role'] = 'admin';
}

echo "<h2>🔍 Edit Booking Link Debug</h2>";

// Test what booking ID we get
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📊 URL Parameters Received</h3>";
echo "<p><strong>GET['id']:</strong> " . ($_GET['id'] ?? 'Not set') . "</p>";
echo "<p><strong>Parsed booking_id:</strong> " . $booking_id . "</p>";
echo "<p><strong>Full URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Query String:</strong> " . $_SERVER['QUERY_STRING'] . "</p>";
echo "</div>";

// Check what edit_booking.php logic would do
if ($booking_id <= 0) {
    echo "<div class='alert alert-warning'>⚠️ <strong>REDIRECT TRIGGER FOUND!</strong><br>";
    echo "The booking_id is $booking_id, which triggers redirect to bookings.php<br>";
    echo "This is why you're seeing the same page again!</div>";
} else {
    echo "<div class='alert alert-success'>✅ Booking ID is valid ($booking_id)</div>";
}

// Test if booking exists
if ($booking_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        echo "<div class='alert alert-success'>✅ Booking exists in database</div>";
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>📋 Booking Details</h4>";
        echo "<p><strong>ID:</strong> " . $booking['id'] . "</p>";
        echo "<p><strong>Customer ID:</strong> " . $booking['customer_id'] . "</p>";
        echo "<p><strong>Event Date:</strong> " . $booking['event_date'] . "</p>";
        echo "<p><strong>Status:</strong> " . $booking['booking_status'] . "</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Booking not found in database</div>";
    }
}

// Show all available bookings for testing
echo "<h3>📋 Available Bookings for Testing</h3>";
$stmt = $pdo->query("SELECT id, customer_id, event_date, booking_status FROM bookings ORDER BY id DESC LIMIT 10");
$bookings = $stmt->fetchAll();

if ($bookings) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Customer ID</th><th>Event Date</th><th>Status</th><th>Test Links</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>#" . $booking['id'] . "</td>";
        echo "<td>" . $booking['customer_id'] . "</td>";
        echo "<td>" . $booking['event_date'] . "</td>";
        echo "<td>" . $booking['booking_status'] . "</td>";
        echo "<td>";
        echo "<a href='?id=" . $booking['id'] . "' class='btn btn-sm btn-primary'>Test ID " . $booking['id'] . "</a> ";
        echo "<a href='../admin/edit_booking.php?id=" . $booking['id'] . "' class='btn btn-sm btn-success' target='_blank'>Direct Edit</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>No bookings found</div>";
}

// Test the exact link format from bookings.php
echo "<h3>🔗 Simulated Admin Bookings Edit Buttons</h3>";

if ($bookings) {
    echo "<div style='background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd;'>";
    echo "<h4>Copy of actual admin bookings.php edit buttons:</h4>";
    
    foreach (array_slice($bookings, 0, 3) as $booking) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #eee;'>";
        echo "<strong>Booking #" . $booking['id'] . "</strong><br>";
        
        // Exact copy of the button group from bookings.php
        echo "<div class='btn-group' role='group'>";
        echo "<button type='button' class='btn btn-sm btn-outline-primary' onclick='alert(\"View: " . $booking['id'] . "\")' title='View Details'>";
        echo "<i class='fas fa-eye'></i>";
        echo "</button>";
        echo "<a href='edit_booking.php?id=" . $booking['id'] . "' class='btn btn-sm btn-outline-info' title='Edit Booking'>";
        echo "<i class='fas fa-edit'></i>";
        echo "</a>";
        echo "<button type='button' class='btn btn-sm btn-outline-danger' onclick='alert(\"Delete: " . $booking['id'] . "\")' title='Delete Booking'>";
        echo "<i class='fas fa-trash'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
}

// Debug browser information
echo "<h3>🌐 Browser & Server Info</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Referer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'None') . "</p>";
echo "<p><strong>Current Path:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "</div>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .alert { margin: 10px 0; }
        h2, h3, h4 { color: #333; }
    </style>
</head>
<body>

<div style="margin-top: 40px; padding: 20px; background: #f8d7da; border-radius: 5px; border: 1px solid #f5c6cb;">
    <h4>🚨 Why Edit Button Redirects Back to Bookings</h4>
    <p><strong>Root Cause:</strong> The edit_booking.php file checks if booking ID is valid. If not, it redirects back to bookings.php</p>
    
    <p><strong>Code causing redirect:</strong></p>
    <pre style="background: #fff; padding: 10px; border-radius: 3px;">
if ($booking_id <= 0) {
    redirectTo('bookings.php');  // ← This line causes redirect back!
}
</pre>
    
    <p><strong>Common causes:</strong></p>
    <ul>
        <li>Booking ID not passed in URL (?id= missing)</li>
        <li>Invalid booking ID (0, negative, or non-existent)</li>
        <li>JavaScript preventing link from working</li>
        <li>DataTables interference with links</li>
    </ul>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Debug page loaded');
    
    // Test if links are being intercepted
    $('a').on('click', function(e) {
        const href = $(this).attr('href');
        console.log('Link clicked:', href);
        
        // Don't prevent default for actual testing
        // e.preventDefault();
    });
});
</script>

</body>
</html>