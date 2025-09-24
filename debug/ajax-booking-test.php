<?php
/**
 * Test Booking Details AJAX - Debug Tool
 */

session_start();
require_once '../includes/config.php';

echo "<h2>🔍 Booking Details AJAX Test</h2>";

// Set up test session (simulate logged in customer)
if (!isset($_SESSION['user_id'])) {
    // Get the customer who owns booking 1000
    $stmt = $pdo->prepare("SELECT customer_id FROM bookings WHERE id = 1000");
    $stmt->execute();
    $booking = $stmt->fetch();
    
    if ($booking) {
        $_SESSION['user_id'] = $booking['customer_id'];
        $_SESSION['user_role'] = 'customer';
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Test session created - User ID: " . $_SESSION['user_id'] . " (Customer)";
        echo "</div>";
    }
}

if (isset($_GET['test'])) {
    echo "<h3>🧪 Testing AJAX Handler</h3>";
    
    // Simulate the AJAX request
    $_POST['action'] = 'get_booking_details';
    $_POST['booking_id'] = 1000;
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Request Data:</h4>";
    echo "<pre>";
    echo "POST Data: " . json_encode($_POST, JSON_PRETTY_PRINT);
    echo "\nSession: " . json_encode([
        'user_id' => $_SESSION['user_id'] ?? 'Not set',
        'user_role' => $_SESSION['user_role'] ?? 'Not set'
    ], JSON_PRETTY_PRINT);
    echo "</pre>";
    echo "</div>";
    
    echo "<h4>📊 Response:</h4>";
    echo "<div style='background: white; border: 1px solid #ddd; padding: 15px; border-radius: 5px;'>";
    
    // Capture the output from ajax_handler.php
    ob_start();
    
    try {
        // Include the ajax handler logic directly
        $action = $_POST['action'];
        
        if ($action === 'get_booking_details') {
            $booking_id = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
            
            if (!$booking_id) {
                throw new Exception('Invalid booking ID');
            }
            
            // Build query with access control
            $where_clause = "WHERE b.id = ?";
            $params = [$booking_id];
            
            // Add access control for customers
            if ($_SESSION['user_role'] === 'customer') {
                $where_clause .= " AND b.customer_id = ?";
                $params[] = $_SESSION['user_id'];
            }
            
            $stmt = $pdo->prepare("
                SELECT b.*, u.full_name as customer_name, u.email, u.phone,
                       wp.name as package_name, wp.price as package_price
                FROM bookings b
                LEFT JOIN users u ON b.customer_id = u.id
                LEFT JOIN wedding_packages wp ON b.package_id = wp.id
                {$where_clause}
            ");
            $stmt->execute($params);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            // Get assigned vendors
            $stmt = $pdo->prepare("
                SELECT bv.*, v.business_name, v.service_type, u.full_name as vendor_name
                FROM booking_vendors bv
                JOIN vendors v ON bv.vendor_id = v.id
                JOIN users u ON v.user_id = u.id
                WHERE bv.booking_id = ?
                ORDER BY bv.service_type
            ");
            $stmt->execute([$booking_id]);
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get payments
            $stmt = $pdo->prepare("
                SELECT * FROM payments 
                WHERE booking_id = ? 
                ORDER BY payment_date DESC
            ");
            $stmt->execute([$booking_id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6><i class='fas fa-user text-primary'></i> Customer Information</h6>";
            $html .= "<p><strong>Name:</strong> " . htmlspecialchars($booking['customer_name']) . "</p>";
            $html .= "<p><strong>Email:</strong> " . htmlspecialchars($booking['email']) . "</p>";
            $html .= "<p><strong>Phone:</strong> " . htmlspecialchars($booking['phone'] ?: 'Not provided') . "</p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6><i class='fas fa-calendar text-info'></i> Event Details</h6>";
            $html .= "<p><strong>Date:</strong> " . date('M j, Y', strtotime($booking['event_date'])) . "</p>";
            $html .= "<p><strong>Time:</strong> " . date('g:i A', strtotime($booking['event_time'])) . "</p>";
            $html .= "<p><strong>Venue:</strong> " . htmlspecialchars($booking['venue_name'] ?: 'Not specified') . "</p>";
            $html .= "<p><strong>Guests:</strong> " . number_format($booking['guest_count']) . "</p>";
            $html .= "</div>";
            $html .= "</div>";
            
            // Package Information
            if ($booking['package_name']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6><i class='fas fa-box text-success'></i> Package Information</h6>";
                $html .= "<div class='row'>";
                $html .= "<div class='col-md-6'>";
                $html .= "<p><strong>Package:</strong> " . htmlspecialchars($booking['package_name']) . "</p>";
                $html .= "</div>";
                $html .= "<div class='col-md-6'>";
                $html .= "<p><strong>Package Price:</strong> RM " . number_format($booking['package_price'], 2) . "</p>";
                $html .= "</div>";
                $html .= "</div>";
                $html .= "</div>";
            }
            
            // Pricing Information
            $html .= "<div class='mt-3'>";
            $html .= "<h6><i class='fas fa-money-bill text-warning'></i> Pricing Information</h6>";
            $html .= "<div class='row'>";
            $html .= "<div class='col-md-4'>";
            $html .= "<p><strong>Total Amount:</strong> RM " . number_format($booking['total_amount'], 2) . "</p>";
            $html .= "</div>";
            $html .= "<div class='col-md-4'>";
            $html .= "<p><strong>Paid Amount:</strong> RM " . number_format($booking['paid_amount'], 2) . "</p>";
            $html .= "</div>";
            $html .= "<div class='col-md-4'>";
            $remaining = $booking['total_amount'] - $booking['paid_amount'];
            $html .= "<p><strong>Balance:</strong> <span class='" . ($remaining > 0 ? 'text-danger' : 'text-success') . "'>RM " . number_format($remaining, 2) . "</span></p>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "</div>";

            // Payment Status
            $html .= "<div class='mt-3'>";
            $html .= "<h6><i class='fas fa-info-circle text-info'></i> Status Information</h6>";
            $html .= "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $booking_status_color = [
                'pending' => 'warning',
                'confirmed' => 'success',
                'completed' => 'info',
                'cancelled' => 'danger'
            ][$booking['booking_status']] ?? 'secondary';
            $html .= "<p><strong>Booking Status:</strong> <span class='badge badge-{$booking_status_color}'>" . ucfirst($booking['booking_status']) . "</span></p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $payment_status_color = [
                'pending' => 'warning',
                'partial' => 'info',
                'paid' => 'success',
                'refunded' => 'secondary'
            ][$booking['payment_status']] ?? 'secondary';
            $html .= "<p><strong>Payment Status:</strong> <span class='badge badge-{$payment_status_color}'>" . ucfirst($booking['payment_status']) . "</span></p>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "</div>";

            // Special Requests
            if ($booking['special_requests']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6><i class='fas fa-clipboard-list text-secondary'></i> Special Requests</h6>";
                $html .= "<p class='border p-2 rounded bg-light'>" . nl2br(htmlspecialchars($booking['special_requests'])) . "</p>";
                $html .= "</div>";
            }

            echo json_encode(['success' => true, 'html' => $html]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $output = ob_get_clean();
    
    // Try to decode as JSON for pretty printing
    $json_data = json_decode($output, true);
    if ($json_data) {
        echo "<h5>JSON Response:</h5>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . json_encode($json_data, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($json_data['success'] && isset($json_data['html'])) {
            echo "<h5>Rendered HTML:</h5>";
            echo "<div style='border: 1px solid #ddd; padding: 15px; background: white;'>";
            echo $json_data['html'];
            echo "</div>";
        }
    } else {
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
    echo "</div>";
    
} else {
    // Show test form
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🧪 Test Booking Details Modal</h4>";
    echo "<p>This tool tests the AJAX handler for booking details to identify and fix issues.</p>";
    
    // Show current session status
    echo "<h5>Current Session:</h5>";
    echo "<ul>";
    echo "<li><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</li>";
    echo "<li><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'Not set') . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='?test=1' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🚀 Run Test";
    echo "</a>";
    echo "</div>";
}
?>

<style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 20px; 
        background: #f5f5f5; 
        line-height: 1.6;
    }
    h2, h3, h4, h5 { color: #333; }
    pre { 
        background: #f8f9fa; 
        padding: 10px; 
        border-radius: 5px; 
        overflow-x: auto; 
    }
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    .badge-success { background-color: #28a745; color: white; }
    .badge-warning { background-color: #ffc107; color: black; }
    .badge-info { background-color: #17a2b8; color: white; }
    .badge-danger { background-color: #dc3545; color: white; }
</style>