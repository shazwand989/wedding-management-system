<?php
/**
 * Booking Details Rendering Test
 * Tests the complete modal workflow with proper styling
 */

session_start();
require_once '../includes/config.php';

// Set up test session
$stmt = $pdo->prepare("SELECT customer_id FROM bookings WHERE id = 1000");
$stmt->execute();
$booking = $stmt->fetch();

if ($booking) {
    $_SESSION['user_id'] = $booking['customer_id'];
    $_SESSION['user_role'] = 'customer';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Styled Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
    body { background: #f4f6f9; padding: 20px; }
    
    /* Booking Details Modal Styling */
    .modal-dialog {
        max-width: 900px;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-body h6 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .modal-body p {
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }

    .modal-body .row {
        margin-bottom: 1rem;
    }

    .badge {
        font-size: 0.75em;
        padding: 0.5em 0.75em;
    }

    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #17a2b8 !important; }

    .border { border: 1px solid #dee2e6 !important; }
    .bg-light { background-color: #f8f9fa !important; }

    /* Icon spacing */
    i { margin-right: 0.5rem; }
    
    /* Test styles */
    .test-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body>

<div class="container">
    <h2>🎨 Booking Details - Styled Modal Test</h2>
    
    <div class="test-section">
        <h4>📋 Test Status</h4>
        <p><strong>Session User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Session Role:</strong> <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
        
        <button class="btn btn-primary btn-lg" onclick="testBookingDetails()">
            <i class="fas fa-eye"></i> Test Styled Modal
        </button>
        
        <button class="btn btn-info ml-2" onclick="testRawAjax()">
            <i class="fas fa-code"></i> Test Raw AJAX
        </button>
    </div>

    <div id="rawResponse" class="test-section" style="display: none;">
        <h4>📡 Raw AJAX Response</h4>
        <div id="rawContent"></div>
    </div>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check text-primary"></i> Booking Details
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading booking details...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function testBookingDetails() {
    console.log('Testing booking details modal...');
    
    $('#bookingDetailsModal').modal('show');
    $('#bookingDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading booking details...</div>');
    
    $.ajax({
        url: '../includes/ajax_handler.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'get_booking_details',
            booking_id: 1000
        },
        success: function(response) {
            console.log('Response received:', response);
            
            if (response.success) {
                $('#bookingDetailsContent').html(response.html);
                console.log('Modal content updated successfully');
            } else {
                $('#bookingDetailsContent').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle"></i> Error: ' + 
                    (response.message || 'Unknown error') + 
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#bookingDetailsContent').html(
                '<div class="alert alert-danger">' +
                '<i class="fas fa-times-circle"></i> Error loading booking details: ' + error +
                '</div>'
            );
        }
    });
}

function testRawAjax() {
    $('#rawResponse').show();
    $('#rawContent').html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    
    $.ajax({
        url: '../includes/ajax_handler.php',
        method: 'POST',
        data: {
            action: 'get_booking_details',
            booking_id: 1000
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                $('#rawContent').html(
                    '<div class="alert alert-success">✅ Valid JSON Response</div>' +
                    '<h5>JSON Structure:</h5>' +
                    '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px;">' + 
                    JSON.stringify(data, null, 2) + 
                    '</pre>'
                );
            } catch (e) {
                $('#rawContent').html(
                    '<div class="alert alert-warning">⚠️ Raw HTML Response (not JSON)</div>' +
                    '<h5>Raw Response:</h5>' +
                    '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;">' + 
                    $('<div>').text(response).html() + 
                    '</pre>'
                );
            }
        },
        error: function(xhr, status, error) {
            $('#rawContent').html('<div class="alert alert-danger">❌ AJAX Error: ' + error + '</div>');
        }
    });
}

$(document).ready(function() {
    console.log('Page ready, jQuery:', $.fn.jquery);
});
</script>

</body>
</html>