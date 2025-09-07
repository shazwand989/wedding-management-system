<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$type = $_GET['type'] ?? '';
$vendor_id = (int)($_GET['vendor_id'] ?? 0);
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? '';

// Verify vendor ownership for vendor exports
if ($_SESSION['user_role'] === 'vendor') {
    $stmt = $pdo->prepare("SELECT user_id FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $vendor_data = $stmt->fetch();
    
    if (!$vendor_data || $vendor_data['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        exit('Access denied');
    }
}

$filename = '';
$data = [];

switch ($type) {
    case 'transactions':
        $stmt = $pdo->prepare("
            SELECT 
                p.created_at as date,
                p.amount,
                p.payment_method,
                p.status,
                b.event_date,
                u.full_name as customer_name,
                b.event_type
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON b.customer_id = u.id
            JOIN booking_vendors bv ON b.id = bv.booking_id
            WHERE bv.vendor_id = ? AND YEAR(p.created_at) = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$vendor_id, $year]);
        $data = $stmt->fetchAll();
        $filename = "transactions_{$year}.csv";
        break;

    case 'bookings':
        $where_clause = "bv.vendor_id = ?";
        $params = [$vendor_id];
        
        if ($month) {
            $where_clause .= " AND YEAR(b.event_date) = ? AND MONTH(b.event_date) = ?";
            $params[] = $year;
            $params[] = $month;
            $filename = "bookings_{$year}_{$month}.csv";
        } else {
            $where_clause .= " AND YEAR(b.event_date) = ?";
            $params[] = $year;
            $filename = "bookings_{$year}.csv";
        }

        $stmt = $pdo->prepare("
            SELECT 
                b.id as booking_id,
                b.event_date,
                b.event_type,
                b.guests_count,
                b.venue,
                b.budget,
                b.status as booking_status,
                bv.status as vendor_status,
                bv.agreed_price,
                u.full_name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                b.created_at
            FROM bookings b
            JOIN booking_vendors bv ON b.id = bv.booking_id
            JOIN users u ON b.customer_id = u.id
            WHERE $where_clause
            ORDER BY b.event_date DESC
        ");
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        break;

    case 'earnings':
        $stmt = $pdo->prepare("
            SELECT 
                YEAR(b.event_date) as year,
                MONTH(b.event_date) as month,
                COUNT(*) as bookings,
                SUM(bv.agreed_price) as earnings,
                AVG(bv.agreed_price) as avg_booking_value
            FROM booking_vendors bv
            JOIN bookings b ON bv.booking_id = b.id
            WHERE bv.vendor_id = ? AND bv.status = 'confirmed' AND bv.agreed_price IS NOT NULL
            AND YEAR(b.event_date) = ?
            GROUP BY YEAR(b.event_date), MONTH(b.event_date)
            ORDER BY YEAR(b.event_date), MONTH(b.event_date)
        ");
        $stmt->execute([$vendor_id, $year]);
        $earnings_data = $stmt->fetchAll();
        
        // Add month names to results
        $month_names = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        foreach ($earnings_data as &$row) {
            $row['month_name'] = $month_names[$row['month']];
        }
        
        $data = $earnings_data;
        $filename = "earnings_{$year}.csv";
        break;

    default:
        http_response_code(400);
        exit('Invalid export type');
}

if (empty($data)) {
    http_response_code(404);
    exit('No data found');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');

// Output CSV
$output = fopen('php://output', 'w');

// Get column headers from first row
if (!empty($data)) {
    $headers = array_keys($data[0]);
    fputcsv($output, $headers);
    
    // Output data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>