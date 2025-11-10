<?php
/**
 * Admin Bookings Edit Button Test
 * Tests the edit booking functionality
 */

define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Check admin access
if (!isLoggedIn() || getUserRole() !== 'admin') {
    // For testing, simulate admin login
    $_SESSION['user_id'] = 1; // Assume admin user ID is 1
    $_SESSION['user_role'] = 'admin';
}

echo "<h2>🔧 Admin Edit Booking Test</h2>";

// Test if booking exists
$booking_id = 1000;
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    echo "<div class='alert alert-danger'>❌ Booking #$booking_id not found!</div>";
    exit;
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📋 Booking Information</h3>";
echo "<p><strong>Booking ID:</strong> #" . $booking['id'] . "</p>";
echo "<p><strong>Customer ID:</strong> " . $booking['customer_id'] . "</p>";
echo "<p><strong>Event Date:</strong> " . $booking['event_date'] . "</p>";
echo "<p><strong>Status:</strong> " . $booking['booking_status'] . "</p>";
echo "</div>";

// Test different link approaches
echo "<h3>🔗 Test Edit Links</h3>";

echo "<div style='margin: 20px 0;'>";
echo "<h4>1. Direct Link (Same as in bookings.php)</h4>";
echo "<a href='edit_booking.php?id=$booking_id' class='btn btn-primary' target='_blank'>";
echo "<i class='fas fa-edit'></i> Edit Booking (Direct Link)";
echo "</a>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<h4>2. Full URL Link</h4>";
echo "<a href='https://shazwan-danial.my/wedding-management-system/admin/edit_booking.php?id=$booking_id' class='btn btn-success' target='_blank'>";
echo "<i class='fas fa-edit'></i> Edit Booking (Full URL)";
echo "</a>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<h4>3. JavaScript Test</h4>";
echo "<button class='btn btn-info' onclick='testEditBooking($booking_id)'>";
echo "<i class='fas fa-code'></i> Test via JavaScript";
echo "</button>";
echo "</div>";

// Test file permissions and accessibility
echo "<h3>📁 File System Checks</h3>";

$edit_file = '/var/www/shazwan-danial.my/public/wedding-management-system/admin/edit_booking.php';
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>File Path:</strong> $edit_file</p>";
echo "<p><strong>File Exists:</strong> " . (file_exists($edit_file) ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>File Readable:</strong> " . (is_readable($edit_file) ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>File Size:</strong> " . (file_exists($edit_file) ? filesize($edit_file) . ' bytes' : 'N/A') . "</p>";
echo "</div>";

// Test URL accessibility
echo "<h3>🌐 URL Accessibility Test</h3>";
echo "<div id='urlTest'></div>";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .btn { margin: 5px; }
        h2, h3, h4 { color: #333; }
    </style>
</head>
<body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function testEditBooking(bookingId) {
    const url = `edit_booking.php?id=${bookingId}`;
    console.log('Testing edit booking URL:', url);
    
    // Try to open in new tab
    const newWindow = window.open(url, '_blank');
    
    if (newWindow) {
        alert(`✅ Successfully opened edit booking page for ID: ${bookingId}`);
    } else {
        alert(`❌ Failed to open edit booking page. Check popup blocker or console for errors.`);
    }
}

// Test URL accessibility via AJAX
$(document).ready(function() {
    const testUrl = 'edit_booking.php?id=<?php echo $booking_id; ?>';
    
    $('#urlTest').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Testing URL accessibility...</div>');
    
    $.ajax({
        url: testUrl,
        method: 'HEAD', // Just check if URL is accessible
        success: function() {
            $('#urlTest').html('<div class="alert alert-success">✅ URL is accessible via AJAX</div>');
        },
        error: function(xhr, status, error) {
            $('#urlTest').html(`
                <div class="alert alert-danger">
                    ❌ URL accessibility test failed<br>
                    <strong>Status:</strong> ${xhr.status}<br>
                    <strong>Error:</strong> ${error}
                </div>
            `);
        }
    });
    
    console.log('Page loaded, ready for testing');
});
</script>

<div style="margin-top: 40px; padding: 20px; background: white; border-radius: 5px;">
    <h4>🔍 Debugging Information</h4>
    <p><strong>Current User Role:</strong> <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
    <p><strong>Current User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
    <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'Not available'; ?></p>
    <p><strong>Server Name:</strong> <?php echo $_SERVER['SERVER_NAME'] ?? 'Not available'; ?></p>
</div>

</body>
</html>