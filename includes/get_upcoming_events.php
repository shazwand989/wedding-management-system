<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get upcoming events for admin dashboard
    $stmt = $pdo->query("
        SELECT b.id, b.event_date, b.event_time, b.venue_name, b.booking_status,
               u.full_name as customer_name
        FROM bookings b
        JOIN users u ON b.customer_id = u.id
        WHERE b.event_date >= CURDATE() 
        AND b.booking_status IN ('pending', 'confirmed')
        ORDER BY b.event_date ASC
        LIMIT 6
    ");
    
    $events = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load events'
    ]);
}
?>
