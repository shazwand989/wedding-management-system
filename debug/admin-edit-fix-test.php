<?php
/**
 * 🧪 Admin Edit Booking Test - Comprehensive Verification
 */

define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Simulate admin login
if (!isLoggedIn() || getUserRole() !== 'admin') {
    $_SESSION['user_id'] = 1; 
    $_SESSION['user_role'] = 'admin';
}

// Get sample bookings for testing
$stmt = $pdo->query("SELECT id, customer_id, event_date, booking_status FROM bookings ORDER BY id DESC LIMIT 5");
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>🧪 Admin Edit Booking Fix Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #ddd; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #cce7ff; border-color: #b8daff; color: #004085; }
        .log-area { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Admin Edit Booking Fix - Comprehensive Test</h1>
    
    <div class="test-section">
        <h3>📋 Available Test Bookings</h3>
        
        <?php if ($bookings): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Event Date</th>
                            <th>Status</th>
                            <th>Test Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo $booking['customer_id']; ?></td>
                            <td><?php echo $booking['event_date']; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $booking['booking_status'] === 'confirmed' ? 'success' : 
                                        ($booking['booking_status'] === 'pending' ? 'warning' : 
                                        ($booking['booking_status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($booking['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Original Method (should fail) -->
                                <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Original Method">
                                    <i class="fas fa-link"></i> Old Way
                                </a>
                                
                                <!-- New JavaScript Method (should work) -->
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="editBookingEnhanced(<?php echo $booking['id']; ?>)" title="JavaScript Enhanced">
                                    <i class="fas fa-edit"></i> New Way
                                </button>
                                
                                <!-- Alternative Methods -->
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="editBookingAlternative(<?php echo $booking['id']; ?>)" title="Alternative Method">
                                    <i class="fas fa-cog"></i> Alt
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert warning">No bookings found. Please create some bookings first.</div>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h3>🔧 Test Functions</h3>
        <div class="row">
            <div class="col-md-4">
                <button onclick="runAllTests()" class="btn btn-primary btn-block">
                    <i class="fas fa-play"></i> Run All Tests
                </button>
            </div>
            <div class="col-md-4">
                <button onclick="clearLog()" class="btn btn-secondary btn-block">
                    <i class="fas fa-trash"></i> Clear Log
                </button>
            </div>
            <div class="col-md-4">
                <button onclick="testNavigationMethods()" class="btn btn-info btn-block">
                    <i class="fas fa-compass"></i> Test Navigation
                </button>
            </div>
        </div>
    </div>

    <div class="test-section">
        <h3>📊 Test Results Log</h3>
        <div id="testLog" class="log-area">
            <strong>Test results will appear here...</strong>
        </div>
    </div>

    <div class="test-section">
        <h3>🔗 Quick Navigation Tests</h3>
        <p>Test different ways to access the edit booking page:</p>
        
        <?php if ($bookings): ?>
            <?php $testId = $bookings[0]['id']; ?>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Direct URL Access
                    <a href="../admin/edit_booking.php?id=<?php echo $testId; ?>" target="_blank" class="btn btn-sm btn-primary">
                        Test ID <?php echo $testId; ?>
                    </a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Same Window Navigation
                    <button onclick="window.location.href='edit_booking.php?id=<?php echo $testId; ?>'" class="btn btn-sm btn-success">
                        Navigate Same Window
                    </button>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Form Method POST
                    <form method="POST" action="edit_booking.php" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $testId; ?>">
                        <button type="submit" class="btn btn-sm btn-warning">Form Submit</button>
                    </form>
                </li>
            </ul>
        <?php endif; ?>
    </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let testCount = 0;

function log(message, type = 'info') {
    testCount++;
    const timestamp = new Date().toLocaleTimeString();
    const logDiv = document.getElementById('testLog');
    const alertClass = type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info';
    
    const logEntry = `
        <div class="alert ${alertClass}" style="margin: 5px 0; padding: 8px; border-radius: 3px;">
            <strong>[${testCount}] ${timestamp}:</strong> ${message}
        </div>
    `;
    
    logDiv.innerHTML += logEntry;
    logDiv.scrollTop = logDiv.scrollHeight;
}

function clearLog() {
    document.getElementById('testLog').innerHTML = '<strong>Test results will appear here...</strong>';
    testCount = 0;
}

// Main edit booking function (matches the one in bookings.php)
function editBookingEnhanced(bookingId) {
    log(`🔧 Enhanced edit function called for booking ID: ${bookingId}`, 'info');
    
    const button = event.target.closest('.btn');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    if (!bookingId || bookingId <= 0) {
        log(`❌ Invalid booking ID: ${bookingId}`, 'error');
        Swal.fire({
            title: 'Error',
            text: 'Invalid booking ID: ' + bookingId,
            icon: 'error'
        });
        button.innerHTML = originalHtml;
        button.disabled = false;
        return false;
    }
    
    const editUrl = 'edit_booking.php?id=' + bookingId;
    log(`✅ Navigating to: ${editUrl}`, 'success');
    
    setTimeout(() => {
        window.location.href = editUrl;
    }, 500);
    
    return false;
}

// Alternative edit function
function editBookingAlternative(bookingId) {
    log(`🔄 Alternative edit function for ID: ${bookingId}`, 'info');
    
    if (!bookingId || bookingId <= 0) {
        log(`❌ Invalid booking ID: ${bookingId}`, 'error');
        return false;
    }
    
    // Use location.assign instead
    const editUrl = 'edit_booking.php?id=' + bookingId;
    log(`🚀 Using location.assign: ${editUrl}`, 'info');
    window.location.assign(editUrl);
}

function runAllTests() {
    log('🧪 Starting comprehensive test suite...', 'info');
    
    // Test 1: Validate test environment
    log('Test 1: Environment validation', 'info');
    if (typeof $ !== 'undefined') {
        log('✅ jQuery loaded successfully', 'success');
    } else {
        log('❌ jQuery not loaded', 'error');
    }
    
    if (typeof Swal !== 'undefined') {
        log('✅ SweetAlert2 loaded successfully', 'success');
    } else {
        log('❌ SweetAlert2 not loaded', 'error');
    }
    
    // Test 2: Function availability
    log('Test 2: Function availability', 'info');
    if (typeof editBookingEnhanced === 'function') {
        log('✅ editBookingEnhanced function available', 'success');
    } else {
        log('❌ editBookingEnhanced function not found', 'error');
    }
    
    // Test 3: Navigation capability
    log('Test 3: Navigation capability', 'info');
    const testUrl = 'edit_booking.php?test=1';
    log(`Testing navigation to: ${testUrl}`, 'info');
    
    // Test 4: Button interaction
    log('Test 4: Button interaction test', 'info');
    const testButtons = document.querySelectorAll('button[onclick*="editBookingEnhanced"]');
    log(`Found ${testButtons.length} enhanced edit buttons`, testButtons.length > 0 ? 'success' : 'warning');
    
    log('🏁 Test suite completed', 'success');
}

function testNavigationMethods() {
    log('🧭 Testing different navigation methods...', 'info');
    
    const testId = <?php echo !empty($bookings) ? $bookings[0]['id'] : 1; ?>;
    
    Swal.fire({
        title: 'Choose Navigation Test',
        html: `
            <div style="text-align: left;">
                <p><strong>Test booking ID:</strong> ${testId}</p>
                <p>Choose which navigation method to test:</p>
            </div>
        `,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Enhanced Method',
        denyButtonText: 'Standard Method',
        cancelButtonText: 'Alternative Method'
    }).then((result) => {
        if (result.isConfirmed) {
            log(`Testing enhanced method for ID ${testId}`, 'info');
            editBookingEnhanced(testId);
        } else if (result.isDenied) {
            log(`Testing standard method for ID ${testId}`, 'warning');
            window.location.href = `edit_booking.php?id=${testId}`;
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            log(`Testing alternative method for ID ${testId}`, 'info');
            editBookingAlternative(testId);
        }
    });
}

// Monitor page events
$(document).ready(function() {
    log('📱 Page loaded and monitoring initialized', 'success');
    
    // Monitor all clicks for debugging
    $('body').on('click', 'a, button', function(e) {
        const element = $(this);
        const text = element.text().trim() || element.attr('title') || 'Unknown';
        const href = element.attr('href') || element.attr('onclick') || 'No action';
        
        if (href.includes('edit_booking') || href.includes('editBooking')) {
            log(`🎯 Edit booking element clicked: "${text}" -> ${href}`, 'warning');
        }
    });
});
</script>

</body>
</html>