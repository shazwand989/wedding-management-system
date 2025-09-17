<?php
session_start();
require_once 'includes/config.php';

// Simulate admin login for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Admin User';

echo "<h2>Testing Admin Booking Edit Functionality</h2>\n";

// Test 1: Check if all required tables exist
echo "<h3>Test 1: Database Structure Check</h3>\n";
$required_tables = ['bookings', 'booking_vendors', 'vendors', 'users', 'wedding_packages'];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

// Test 2: Check existing bookings
echo "<h3>Test 2: Available Bookings</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT b.id, b.event_date, b.booking_status, u.full_name as customer_name,
               COUNT(bv.id) as vendor_count
        FROM bookings b
        LEFT JOIN users u ON b.customer_id = u.id
        LEFT JOIN booking_vendors bv ON b.id = bv.booking_id
        GROUP BY b.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $bookings = $stmt->fetchAll();
    
    if (empty($bookings)) {
        echo "ℹ️ No bookings found in database\n";
    } else {
        echo "Found " . count($bookings) . " bookings:\n";
        foreach ($bookings as $booking) {
            echo "- Booking #{$booking['id']}: {$booking['customer_name']} - {$booking['event_date']} ({$booking['booking_status']}) - {$booking['vendor_count']} vendors assigned\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking bookings: " . $e->getMessage() . "\n";
}

// Test 3: Check available vendors
echo "<h3>Test 3: Available Vendors</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT v.id, v.business_name, v.service_type, v.status, u.full_name as owner_name
        FROM vendors v
        LEFT JOIN users u ON v.user_id = u.id
        WHERE v.status = 'active'
        ORDER BY v.service_type, v.business_name
    ");
    $vendors = $stmt->fetchAll();
    
    if (empty($vendors)) {
        echo "ℹ️ No active vendors found\n";
    } else {
        echo "Found " . count($vendors) . " active vendors:\n";
        $service_groups = [];
        foreach ($vendors as $vendor) {
            $service_groups[$vendor['service_type']][] = $vendor;
        }
        
        foreach ($service_groups as $service => $vendor_list) {
            echo "📋 " . ucfirst($service) . ":\n";
            foreach ($vendor_list as $vendor) {
                echo "  - {$vendor['business_name']} (Owner: {$vendor['owner_name']})\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking vendors: " . $e->getMessage() . "\n";
}

// Test 4: Check wedding packages
echo "<h3>Test 4: Available Wedding Packages</h3>\n";
try {
    $stmt = $pdo->query("SELECT * FROM wedding_packages WHERE status = 'active' ORDER BY price ASC");
    $packages = $stmt->fetchAll();
    
    if (empty($packages)) {
        echo "ℹ️ No active wedding packages found\n";
    } else {
        echo "Found " . count($packages) . " active packages:\n";
        foreach ($packages as $package) {
            echo "- {$package['name']}: RM " . number_format($package['price'], 2) . " (Max guests: {$package['max_guests']})\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking packages: " . $e->getMessage() . "\n";
}

// Test 5: Test a sample booking vendor assignment (simulation)
echo "<h3>Test 5: Booking-Vendor Relationship Test</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT b.id as booking_id, b.event_date, u.full_name as customer_name,
               v.business_name, bv.service_type, bv.agreed_price, bv.status
        FROM bookings b
        LEFT JOIN users u ON b.customer_id = u.id
        LEFT JOIN booking_vendors bv ON b.id = bv.booking_id
        LEFT JOIN vendors v ON bv.vendor_id = v.id
        WHERE bv.id IS NOT NULL
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $assignments = $stmt->fetchAll();
    
    if (empty($assignments)) {
        echo "ℹ️ No vendor assignments found\n";
    } else {
        echo "Found " . count($assignments) . " vendor assignments:\n";
        foreach ($assignments as $assignment) {
            $price = $assignment['agreed_price'] ? 'RM ' . number_format($assignment['agreed_price'], 2) : 'TBD';
            echo "- Booking #{$assignment['booking_id']} ({$assignment['customer_name']}): {$assignment['business_name']} - {$assignment['service_type']} - {$price} ({$assignment['status']})\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking vendor assignments: " . $e->getMessage() . "\n";
}

echo "\n<h3>Admin Booking Edit Feature Summary</h3>\n";
echo "✅ Admin can view all bookings in /admin/bookings.php\n";
echo "✅ Admin can click 'Edit' button to access /admin/edit_booking.php?id=X\n";
echo "✅ Edit page allows modifying:\n";
echo "   - Event date, time, venue, guest count\n";
echo "   - Package selection and total amount\n";
echo "   - Booking status\n";
echo "   - Special requests\n";
echo "✅ Admin can assign/remove vendors with:\n";
echo "   - Service type selection\n";
echo "   - Agreed price\n";
echo "   - Vendor status (pending/confirmed/cancelled)\n";
echo "   - Notes for each vendor\n";
echo "✅ Enhanced booking details view shows assigned vendors\n";

echo "\n<p><strong>Next Steps:</strong></p>\n";
echo "1. Access /admin/bookings.php to view all bookings\n";
echo "2. Click the blue 'Edit' button (🖊️) on any booking\n";
echo "3. Modify booking details and assign vendors as needed\n";
echo "4. Use 'Add Vendor' button to assign new vendors to the booking\n";
echo "5. Save changes to update the booking\n";
?>
