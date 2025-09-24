<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details Modal Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body style="padding: 20px; background: #f5f5f5;">

<div class="container">
    <h2>🔍 Booking Details Modal Test</h2>
    
    <div class="alert alert-info">
        <h5>Test Purpose:</h5>
        <p>This page tests the booking details modal functionality to identify and fix AJAX issues.</p>
    </div>

    <!-- Test Button -->
    <div class="text-center mb-4">
        <button class="btn btn-primary btn-lg" onclick="viewBookingDetails(1000)">
            <i class="fas fa-eye"></i> Test Booking Details Modal (ID: 1000)
        </button>
    </div>

    <!-- Debug Console -->
    <div class="card">
        <div class="card-header">
            <h5>📊 Debug Console</h5>
        </div>
        <div class="card-body">
            <div id="debugConsole" style="height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                Ready to test...<br>
            </div>
            <button class="btn btn-sm btn-secondary mt-2" onclick="clearConsole()">Clear Console</button>
        </div>
    </div>

    <!-- Booking Details Modal (copy from bookings.php) -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function log(message) {
    const timestamp = new Date().toLocaleTimeString();
    $('#debugConsole').append(`[${timestamp}] ${message}<br>`);
    $('#debugConsole').scrollTop($('#debugConsole')[0].scrollHeight);
}

function clearConsole() {
    $('#debugConsole').html('Console cleared...<br>');
}

function viewBookingDetails(bookingId) {
    log(`🚀 Starting booking details request for ID: ${bookingId}`);
    
    $('#bookingDetailsModal').modal('show');
    $('#bookingDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    log(`📡 Sending AJAX request to: ../includes/ajax_handler.php`);
    log(`📝 POST Data: action=get_booking_details, booking_id=${bookingId}`);
    
    $.ajax({
        url: '../includes/ajax_handler.php',
        method: 'POST',
        data: {
            action: 'get_booking_details',
            booking_id: bookingId
        },
        beforeSend: function() {
            log(`⏳ Request initiated...`);
        },
        success: function(response) {
            log(`✅ Response received (length: ${response.length} chars)`);
            log(`📄 Raw response: ${response.substring(0, 200)}${response.length > 200 ? '...' : ''}`);
            
            try {
                const data = JSON.parse(response);
                log(`🔍 Parsed JSON successfully: success=${data.success}`);
                
                if (data.success) {
                    $('#bookingDetailsContent').html(data.html);
                    log(`✅ Modal content updated successfully`);
                } else {
                    const errorMsg = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    $('#bookingDetailsContent').html(errorMsg);
                    log(`❌ Server error: ${data.message}`);
                }
            } catch (e) {
                log(`💥 JSON Parse Error: ${e.message}`);
                $('#bookingDetailsContent').html(`<div class="alert alert-danger">Invalid response format: ${response}</div>`);
            }
        },
        error: function(xhr, status, error) {
            log(`💥 AJAX Error: ${status} - ${error}`);
            log(`📊 Status Code: ${xhr.status}`);
            log(`📄 Response Text: ${xhr.responseText}`);
            
            $('#bookingDetailsContent').html(`
                <div class="alert alert-danger">
                    <h6>AJAX Request Failed</h6>
                    <p><strong>Status:</strong> ${status}</p>
                    <p><strong>Error:</strong> ${error}</p>
                    <p><strong>Status Code:</strong> ${xhr.status}</p>
                    ${xhr.responseText ? `<p><strong>Response:</strong> ${xhr.responseText}</p>` : ''}
                </div>
            `);
        },
        complete: function() {
            log(`🏁 Request completed`);
        }
    });
}

$(document).ready(function() {
    log(`🎯 Page loaded, jQuery version: ${$.fn.jquery}`);
    log(`🔧 Bootstrap modal plugin loaded: ${typeof $.fn.modal !== 'undefined' ? 'Yes' : 'No'}`);
    log(`📋 Ready to test booking details modal`);
});
</script>

</body>
</html>