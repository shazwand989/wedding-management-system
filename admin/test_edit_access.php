<?php
// Test admin access to edit booking page
define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

echo "=== Testing Admin Edit Booking Page ===\n";

// Test 1: Check if we can include config
echo "✓ Config file loaded successfully\n";

// Test 2: Check database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $result = $stmt->fetch();
    echo "✓ Database connection working - Found " . $result['count'] . " bookings\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if we have bookings to edit
try {
    $stmt = $pdo->query("SELECT id, customer_id FROM bookings LIMIT 1");
    $booking = $stmt->fetch();
    if ($booking) {
        echo "✓ Sample booking found with ID: " . $booking['id'] . "\n";
        echo "  You can test with URL: admin/edit_booking.php?id=" . $booking['id'] . "\n";
    } else {
        echo "✗ No bookings found in database\n";
        echo "  Please create a booking first\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking bookings: " . $e->getMessage() . "\n";
}

// Test 4: Check vendors table
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vendors");
    $result = $stmt->fetch();
    echo "✓ Vendors table accessible - Found " . $result['count'] . " vendors\n";
} catch (Exception $e) {
    echo "✗ Vendors table error: " . $e->getMessage() . "\n";
}

// Test 5: Check booking_vendors table
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM booking_vendors");
    $result = $stmt->fetch();
    echo "✓ Booking_vendors table accessible - Found " . $result['count'] . " assignments\n";
} catch (Exception $e) {
    echo "✗ Booking_vendors table error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests pass, the edit booking page should work.\n";
echo "Make sure you're:\n";
echo "1. Logged in as an admin\n";
echo "2. Using a valid booking ID in the URL\n";
echo "3. Accessing through web server (not directly)\n";
?>
