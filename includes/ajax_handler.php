<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_upcoming_events':
            // Get upcoming events for the current user
            $user_role = $_SESSION['user_role'];
            $user_id = $_SESSION['user_id'];
            
            if ($user_role === 'customer') {
                $stmt = $pdo->prepare("
                    SELECT id, event_date, event_time, venue_name, booking_status 
                    FROM bookings 
                    WHERE customer_id = ? AND event_date >= CURDATE() 
                    ORDER BY event_date ASC LIMIT 5
                ");
                $stmt->execute([$user_id]);
            } elseif ($user_role === 'vendor') {
                $stmt = $pdo->prepare("
                    SELECT b.id, b.event_date, b.event_time, b.venue_name, b.booking_status,
                           bv.status as vendor_status
                    FROM bookings b
                    JOIN booking_vendors bv ON b.id = bv.booking_id
                    JOIN vendors v ON bv.vendor_id = v.id
                    WHERE v.user_id = ? AND b.event_date >= CURDATE()
                    ORDER BY b.event_date ASC LIMIT 5
                ");
                $stmt->execute([$user_id]);
            } else {
                // Admin - get all upcoming events
                $stmt = $pdo->prepare("
                    SELECT b.id, b.event_date, b.event_time, b.venue_name, b.booking_status,
                           u.full_name as customer_name
                    FROM bookings b
                    LEFT JOIN users u ON b.customer_id = u.id
                    WHERE b.event_date >= CURDATE()
                    ORDER BY b.event_date ASC LIMIT 5
                ");
                $stmt->execute();
            }
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'events' => $events]);
            break;

        case 'get_booking_details':
            $booking_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT b.*, u.full_name as customer_name, u.email, u.phone,
                       wp.name as package_name, wp.price as package_price
                FROM bookings b
                LEFT JOIN users u ON b.customer_id = u.id
                LEFT JOIN wedding_packages wp ON b.package_id = wp.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
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

            // Vendor Information
            if (!empty($vendors)) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6><i class='fas fa-users text-purple'></i> Assigned Vendors</h6>";
                $html .= "<div class='table-responsive'>";
                $html .= "<table class='table table-sm table-bordered'>";
                $html .= "<thead><tr><th>Business</th><th>Service</th><th>Price</th><th>Status</th></tr></thead>";
                $html .= "<tbody>";
                foreach ($vendors as $vendor) {
                    $status_color = [
                        'pending' => 'warning',
                        'confirmed' => 'success', 
                        'cancelled' => 'danger'
                    ][$vendor['status']] ?? 'secondary';
                    
                    $html .= "<tr>";
                    $html .= "<td>" . htmlspecialchars($vendor['business_name']) . "</td>";
                    $html .= "<td>" . ucfirst($vendor['service_type']) . "</td>";
                    $html .= "<td>" . ($vendor['agreed_price'] ? 'RM ' . number_format($vendor['agreed_price'], 2) : 'TBD') . "</td>";
                    $html .= "<td><span class='badge badge-{$status_color}'>" . ucfirst($vendor['status']) . "</span></td>";
                    $html .= "</tr>";
                }
                $html .= "</tbody></table>";
                $html .= "</div>";
                $html .= "</div>";
            }

            // Status Information
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

            // Edit Button for Admin
            if ($_SESSION['role'] === 'admin') {
                $html .= "<div class='mt-4 text-center'>";
                $html .= "<a href='edit_booking.php?id={$booking_id}' class='btn btn-primary'>";
                $html .= "<i class='fas fa-edit'></i> Edit Booking";
                $html .= "</a>";
                $html .= "</div>";
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;

        case 'get_customer_details':
            $customer_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       COUNT(b.id) as total_bookings,
                       SUM(b.total_amount) as total_spent,
                       MAX(b.created_at) as last_booking_date
                FROM users u
                LEFT JOIN bookings b ON u.id = b.customer_id
                WHERE u.id = ? AND u.role = 'customer'
                GROUP BY u.id
            ");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception('Customer not found');
            }
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Customer Information</h6>";
            $html .= "<p><strong>Name:</strong> " . htmlspecialchars($customer['full_name']) . "</p>";
            $html .= "<p><strong>Email:</strong> " . htmlspecialchars($customer['email']) . "</p>";
            $html .= "<p><strong>Phone:</strong> " . htmlspecialchars($customer['phone'] ?: 'Not provided') . "</p>";
            $html .= "<p><strong>Status:</strong> <span class='badge bg-" . ($customer['status'] === 'active' ? 'success' : 'danger') . "'>" . ucfirst($customer['status']) . "</span></p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Booking Statistics</h6>";
            $html .= "<p><strong>Total Bookings:</strong> " . number_format($customer['total_bookings']) . "</p>";
            $html .= "<p><strong>Total Spent:</strong> RM " . number_format($customer['total_spent'] ?: 0, 2) . "</p>";
            if ($customer['last_booking_date']) {
                $html .= "<p><strong>Last Booking:</strong> " . date('M j, Y', strtotime($customer['last_booking_date'])) . "</p>";
            }
            $html .= "</div>";
            $html .= "</div>";
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;

        case 'get_customer_data':
            $customer_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception('Customer not found');
            }
            
            echo json_encode(['success' => true, 'customer' => $customer]);
            break;

        case 'add_customer':
        case 'update_customer':
            if ($_SESSION['user_role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }
            
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $status = $_POST['status'];
            
            if (empty($full_name) || empty($email)) {
                throw new Exception('Full name and email are required');
            }
            
            if ($action === 'add_customer') {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role, full_name, phone, address, status) VALUES (?, ?, 'customer', ?, ?, ?, ?)");
                $stmt->execute([$email, $password, $full_name, $phone, $address, $status]);
                
                echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
            } else {
                $customer_id = (int)$_POST['customer_id'];
                
                // Check if email exists for other users
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $customer_id]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ? AND role = 'customer'");
                $stmt->execute([$full_name, $email, $phone, $address, $status, $customer_id]);
                
                echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
            }
            break;

        case 'get_vendor_details':
            $vendor_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT v.*, u.full_name, u.email, u.phone, u.address,
                       COUNT(bv.id) as total_bookings,
                       AVG(r.rating) as avg_rating,
                       COUNT(r.id) as review_count
                FROM vendors v
                LEFT JOIN users u ON v.user_id = u.id
                LEFT JOIN booking_vendors bv ON v.id = bv.vendor_id
                LEFT JOIN reviews r ON v.id = r.vendor_id
                WHERE v.id = ?
                GROUP BY v.id
            ");
            $stmt->execute([$vendor_id]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vendor) {
                throw new Exception('Vendor not found');
            }
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Vendor Information</h6>";
            $html .= "<p><strong>Business Name:</strong> " . htmlspecialchars($vendor['business_name']) . "</p>";
            $html .= "<p><strong>Owner:</strong> " . htmlspecialchars($vendor['full_name']) . "</p>";
            $html .= "<p><strong>Service Type:</strong> " . ucfirst($vendor['service_type']) . "</p>";
            $html .= "<p><strong>Price Range:</strong> " . htmlspecialchars($vendor['price_range'] ?: 'Not specified') . "</p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Performance</h6>";
            $html .= "<p><strong>Total Bookings:</strong> " . number_format($vendor['total_bookings']) . "</p>";
            if ($vendor['avg_rating']) {
                $html .= "<p><strong>Rating:</strong> " . number_format($vendor['avg_rating'], 1) . "/5 (" . $vendor['review_count'] . " reviews)</p>";
            } else {
                $html .= "<p><strong>Rating:</strong> No ratings yet</p>";
            }
            $html .= "<p><strong>Status:</strong> <span class='badge bg-" . ($vendor['status'] === 'active' ? 'success' : ($vendor['status'] === 'pending' ? 'warning' : 'danger')) . "'>" . ucfirst($vendor['status']) . "</span></p>";
            $html .= "</div>";
            $html .= "</div>";
            if ($vendor['description']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Description</h6>";
                $html .= "<p>" . nl2br(htmlspecialchars($vendor['description'])) . "</p>";
                $html .= "</div>";
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;

        case 'get_package_data':
            $package_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("SELECT * FROM wedding_packages WHERE id = ?");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$package) {
                throw new Exception('Package not found');
            }
            
            echo json_encode(['success' => true, 'package' => $package]);
            break;

        case 'get_package_details':
            $package_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT wp.*, 
                       COUNT(b.id) as total_bookings,
                       SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                       SUM(b.total_amount) as total_revenue
                FROM wedding_packages wp
                LEFT JOIN bookings b ON wp.id = b.package_id
                WHERE wp.id = ?
                GROUP BY wp.id
            ");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$package) {
                throw new Exception('Package not found');
            }
            
            $features = json_decode($package['features'], true) ?: [];
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Package Details</h6>";
            $html .= "<p><strong>Price:</strong> RM " . number_format($package['price'], 2) . "</p>";
            $html .= "<p><strong>Duration:</strong> " . $package['duration_hours'] . " hours</p>";
            $html .= "<p><strong>Max Guests:</strong> " . number_format($package['max_guests']) . "</p>";
            $html .= "<p><strong>Status:</strong> <span class='badge bg-" . ($package['status'] === 'active' ? 'success' : 'secondary') . "'>" . ucfirst($package['status']) . "</span></p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Performance</h6>";
            $html .= "<p><strong>Total Bookings:</strong> " . number_format($package['total_bookings']) . "</p>";
            $html .= "<p><strong>Completed:</strong> " . number_format($package['completed_bookings']) . "</p>";
            $html .= "<p><strong>Total Revenue:</strong> RM " . number_format($package['total_revenue'] ?: 0, 2) . "</p>";
            $html .= "</div>";
            $html .= "</div>";
            if ($package['description']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Description</h6>";
                $html .= "<p>" . nl2br(htmlspecialchars($package['description'])) . "</p>";
                $html .= "</div>";
            }
            if (!empty($features)) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Features</h6>";
                $html .= "<ul>";
                foreach ($features as $feature) {
                    $html .= "<li>" . htmlspecialchars($feature) . "</li>";
                }
                $html .= "</ul>";
                $html .= "</div>";
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;

        case 'get_payment_details':
            $payment_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT p.*, b.id as booking_id, b.total_amount, b.event_date,
                       u.full_name as customer_name, u.email
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Payment Information</h6>";
            $html .= "<p><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</p>";
            $html .= "<p><strong>Method:</strong> " . ucfirst(str_replace('_', ' ', $payment['payment_method'])) . "</p>";
            $html .= "<p><strong>Date:</strong> " . date('M j, Y', strtotime($payment['payment_date'])) . "</p>";
            $html .= "<p><strong>Status:</strong> <span class='badge bg-" . ($payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger')) . "'>" . ucfirst($payment['status']) . "</span></p>";
            if ($payment['transaction_id']) {
                $html .= "<p><strong>Transaction ID:</strong> " . htmlspecialchars($payment['transaction_id']) . "</p>";
            }
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Booking Information</h6>";
            $html .= "<p><strong>Booking ID:</strong> #" . $payment['booking_id'] . "</p>";
            $html .= "<p><strong>Customer:</strong> " . htmlspecialchars($payment['customer_name']) . "</p>";
            $html .= "<p><strong>Event Date:</strong> " . date('M j, Y', strtotime($payment['event_date'])) . "</p>";
            $html .= "<p><strong>Total Amount:</strong> RM " . number_format($payment['total_amount'], 2) . "</p>";
            $html .= "</div>";
            $html .= "</div>";
            if ($payment['notes']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Notes</h6>";
                $html .= "<p>" . nl2br(htmlspecialchars($payment['notes'])) . "</p>";
                $html .= "</div>";
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;

        case 'system_health':
            // Simple system health check
            $health = [
                'database' => 'OK',
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo json_encode(['success' => true, 'status' => 'System is healthy', 'details' => $health]);
            break;

        // Legacy actions for backward compatibility
        case 'update_vendor_status':
            if ($_SESSION['user_role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }
            
            $vendor_id = (int)$_POST['vendor_id'];
            $status = $_POST['status'];
            
            if (!in_array($status, ['active', 'inactive'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE vendors SET status = ? WHERE id = ?");
            $stmt->execute([$status, $vendor_id]);
            
            echo json_encode(['success' => true, 'message' => 'Vendor status updated successfully']);
            break;
            
        case 'update_booking_status':
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Unauthorized');
            }
            
            $booking_id = (int)$_POST['booking_id'];
            $status = $_POST['status'];
            $user_role = $_SESSION['user_role'];
            
            // Check permissions
            if ($user_role === 'vendor') {
                // Vendor can only update booking_vendors status
                $vendor_id = (int)$_POST['vendor_id'];
                $stmt = $pdo->prepare("UPDATE booking_vendors SET status = ? WHERE booking_id = ? AND vendor_id = ?");
                $stmt->execute([$status, $booking_id, $vendor_id]);
            } elseif ($user_role === 'admin') {
                // Admin can update booking status
                $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                $stmt->execute([$status, $booking_id]);
            } else {
                throw new Exception('Unauthorized');
            }
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;

        case 'get_booking_details':
            $booking_vendor_id = (int)$_GET['booking_vendor_id'];
            
            $stmt = $pdo->prepare("
                SELECT bv.*, b.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                       wp.name as package_name, wp.price as package_price,
                       v.business_name as vendor_business_name
                FROM booking_vendors bv
                JOIN bookings b ON bv.booking_id = b.id
                JOIN users u ON b.customer_id = u.id
                LEFT JOIN wedding_packages wp ON b.package_id = wp.id
                LEFT JOIN vendors v ON bv.vendor_id = v.id
                WHERE bv.id = ?
            ");
            $stmt->execute([$booking_vendor_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            $html = "<div class='row'>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Customer Information</h6>";
            $html .= "<p><strong>Name:</strong> " . htmlspecialchars($booking['customer_name']) . "</p>";
            $html .= "<p><strong>Email:</strong> " . htmlspecialchars($booking['customer_email']) . "</p>";
            $html .= "<p><strong>Phone:</strong> " . htmlspecialchars($booking['customer_phone'] ?: 'Not provided') . "</p>";
            $html .= "</div>";
            $html .= "<div class='col-md-6'>";
            $html .= "<h6>Event Details</h6>";
            $html .= "<p><strong>Date:</strong> " . date('M j, Y', strtotime($booking['event_date'])) . "</p>";
            $html .= "<p><strong>Time:</strong> " . date('g:i A', strtotime($booking['event_time'])) . "</p>";
            $html .= "<p><strong>Venue:</strong> " . htmlspecialchars($booking['venue_name'] ?: 'Not specified') . "</p>";
            $html .= "<p><strong>Guests:</strong> " . number_format($booking['guest_count']) . "</p>";
            $html .= "</div>";
            $html .= "</div>";
            
            if ($booking['package_name']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Package Information</h6>";
                $html .= "<p><strong>Package:</strong> " . htmlspecialchars($booking['package_name']) . "</p>";
                $html .= "<p><strong>Package Price:</strong> RM " . number_format($booking['package_price'], 2) . "</p>";
                $html .= "</div>";
            }
            
            if ($booking['special_requests']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Special Requests</h6>";
                $html .= "<p>" . nl2br(htmlspecialchars($booking['special_requests'])) . "</p>";
                $html .= "</div>";
            }
            
            if ($booking['agreed_price']) {
                $html .= "<div class='mt-3'>";
                $html .= "<h6>Service Agreement</h6>";
                $html .= "<p><strong>Agreed Price:</strong> RM " . number_format($booking['agreed_price'], 2) . "</p>";
                if ($booking['notes']) {
                    $html .= "<p><strong>Notes:</strong> " . nl2br(htmlspecialchars($booking['notes'])) . "</p>";
                }
                $html .= "</div>";
            }
            
            echo $html;
            break;

        case 'get_day_events':
            if ($_SESSION['user_role'] !== 'vendor') {
                throw new Exception('Unauthorized');
            }
            
            $vendor_id = (int)$_GET['vendor_id'];
            $date = $_GET['date'];
            
            $stmt = $pdo->prepare("
                SELECT bv.*, b.event_time, b.venue_name, 
                       u.full_name as customer_name, u.phone as customer_phone
                FROM booking_vendors bv
                JOIN bookings b ON bv.booking_id = b.id
                JOIN users u ON b.customer_id = u.id
                WHERE bv.vendor_id = ? AND DATE(b.event_date) = ? AND bv.status = 'confirmed'
                ORDER BY b.event_time ASC
            ");
            $stmt->execute([$vendor_id, $date]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($events)) {
                echo "<p class='text-muted text-center py-3'>No events scheduled for this day.</p>";
            } else {
                foreach ($events as $event) {
                    echo "<div class='border-bottom pb-3 mb-3'>";
                    echo "<h6>" . htmlspecialchars($event['customer_name']) . "</h6>";
                    echo "<p class='mb-1'><i class='fas fa-clock'></i> " . date('g:i A', strtotime($event['event_time'])) . "</p>";
                    if ($event['venue_name']) {
                        echo "<p class='mb-1'><i class='fas fa-map-marker-alt'></i> " . htmlspecialchars($event['venue_name']) . "</p>";
                    }
                    if ($event['customer_phone']) {
                        echo "<p class='mb-1'><i class='fas fa-phone'></i> " . htmlspecialchars($event['customer_phone']) . "</p>";
                    }
                    if ($event['agreed_price']) {
                        echo "<p class='mb-0 text-success'><strong>RM " . number_format($event['agreed_price'], 2) . "</strong></p>";
                    }
                    echo "</div>";
                }
            }
            break;

        case 'upload_portfolio_images':
            if ($_SESSION['user_role'] !== 'vendor') {
                throw new Exception('Unauthorized');
            }
            
            $vendor_id = (int)$_POST['vendor_id'];
            
            // Verify vendor ownership
            $stmt = $pdo->prepare("SELECT user_id FROM vendors WHERE id = ?");
            $stmt->execute([$vendor_id]);
            $vendor_data = $stmt->fetch();
            
            if (!$vendor_data || $vendor_data['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Unauthorized');
            }
            
            if (empty($_FILES['images'])) {
                throw new Exception('No images uploaded');
            }
            
            $upload_dir = '../uploads/portfolio/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $uploaded_images = [];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$key];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        continue;
                    }
                    
                    $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $filename = $vendor_id . '_' . time() . '_' . $key . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $uploaded_images[] = 'uploads/portfolio/' . $filename;
                    }
                }
            }
            
            if (!empty($uploaded_images)) {
                // Get current portfolio images
                $stmt = $pdo->prepare("SELECT portfolio_images FROM vendors WHERE id = ?");
                $stmt->execute([$vendor_id]);
                $current_data = $stmt->fetch();
                
                $current_images = json_decode($current_data['portfolio_images'] ?: '[]', true);
                $updated_images = array_merge($current_images, $uploaded_images);
                
                // Update database
                $stmt = $pdo->prepare("UPDATE vendors SET portfolio_images = ? WHERE id = ?");
                $stmt->execute([json_encode($updated_images), $vendor_id]);
                
                echo json_encode(['success' => true, 'message' => count($uploaded_images) . ' images uploaded successfully']);
            } else {
                throw new Exception('No valid images were uploaded');
            }
            break;

        case 'remove_portfolio_image':
            if ($_SESSION['user_role'] !== 'vendor') {
                throw new Exception('Unauthorized');
            }
            
            $vendor_id = (int)$_POST['vendor_id'];
            $image_index = (int)$_POST['image_index'];
            
            // Verify vendor ownership
            $stmt = $pdo->prepare("SELECT user_id, portfolio_images FROM vendors WHERE id = ?");
            $stmt->execute([$vendor_id]);
            $vendor_data = $stmt->fetch();
            
            if (!$vendor_data || $vendor_data['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Unauthorized');
            }
            
            $current_images = json_decode($vendor_data['portfolio_images'] ?: '[]', true);
            
            if (isset($current_images[$image_index])) {
                $image_path = '../' . $current_images[$image_index];
                
                // Remove from array
                unset($current_images[$image_index]);
                $updated_images = array_values($current_images); // Re-index array
                
                // Update database
                $stmt = $pdo->prepare("UPDATE vendors SET portfolio_images = ? WHERE id = ?");
                $stmt->execute([json_encode($updated_images), $vendor_id]);
                
                // Delete file if it exists
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                
                echo json_encode(['success' => true, 'message' => 'Image removed successfully']);
            } else {
                throw new Exception('Image not found');
            }
            break;

        case 'deactivate_vendor_account':
            if ($_SESSION['user_role'] !== 'vendor') {
                throw new Exception('Unauthorized');
            }
            
            $vendor_id = (int)$_POST['vendor_id'];
            
            // Verify vendor ownership
            $stmt = $pdo->prepare("SELECT user_id FROM vendors WHERE id = ?");
            $stmt->execute([$vendor_id]);
            $vendor_data = $stmt->fetch();
            
            if (!$vendor_data || $vendor_data['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Unauthorized');
            }
            
            // Update vendor status to inactive
            $stmt = $pdo->prepare("UPDATE vendors SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$vendor_id]);
            
            echo json_encode(['success' => true, 'message' => 'Account deactivated successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
