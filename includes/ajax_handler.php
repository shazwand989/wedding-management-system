<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_POST) {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_vendor_status':
                if (!isLoggedIn() || getUserRole() !== 'admin') {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
                
                $vendor_id = (int)$_POST['vendor_id'];
                $status = sanitize($_POST['status']);
                
                if (!in_array($status, ['active', 'inactive'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid status']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE vendors SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $vendor_id])) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Vendor status updated successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                }
                break;
                
            case 'update_booking_status':
                if (!isLoggedIn()) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
                
                $booking_id = (int)$_POST['booking_id'];
                $status = sanitize($_POST['status']);
                $user_role = getUserRole();
                
                // Check permissions
                if ($user_role === 'vendor') {
                    // Vendor can only update booking_vendors status
                    $vendor_id = (int)$_POST['vendor_id'];
                    $stmt = $pdo->prepare("UPDATE booking_vendors SET status = ? WHERE booking_id = ? AND vendor_id = ?");
                    $success = $stmt->execute([$status, $booking_id, $vendor_id]);
                } elseif ($user_role === 'admin') {
                    // Admin can update booking status
                    $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                    $success = $stmt->execute([$status, $booking_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
                
                if ($success) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Status updated successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
