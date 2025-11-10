<?php
/**
 * Admin Edit Button Click Test - Real-time debug
 */

define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Simulate admin login if not logged in
if (!isLoggedIn() || getUserRole() !== 'admin') {
    $_SESSION['user_id'] = 1; 
    $_SESSION['user_role'] = 'admin';
}

// Get first available booking for testing
$stmt = $pdo->query("SELECT id, customer_id, event_date, booking_status FROM bookings ORDER BY id DESC LIMIT 1");
$test_booking = $stmt->fetch();

?>
<!DOCTYPE html>
<html>
<head>
    <title>🔧 Admin Edit Button Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: Arial, sans-serif; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #ddd; }
        .log { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Admin Edit Button Real-Time Test</h1>
    
    <?php if ($test_booking): ?>
    
    <div class="test-section">
        <h3>📋 Test Booking Data</h3>
        <p><strong>ID:</strong> <?php echo $test_booking['id']; ?></p>
        <p><strong>Customer:</strong> <?php echo $test_booking['customer_id']; ?></p>
        <p><strong>Event Date:</strong> <?php echo $test_booking['event_date']; ?></p>
        <p><strong>Status:</strong> <?php echo $test_booking['booking_status']; ?></p>
    </div>

    <div class="test-section">
        <h3>🔗 Button Tests</h3>
        
        <h4>Test 1: Direct HTML Link (Same as admin bookings)</h4>
        <p>This is the EXACT code from admin/bookings.php:</p>
        <div style="border: 2px solid #007bff; padding: 15px; border-radius: 5px; background: #f8f9fa;">
            <a href="edit_booking.php?id=<?php echo $test_booking['id']; ?>" class="btn btn-sm btn-outline-info" title="Edit Booking">
                <i class="fas fa-edit"></i> Edit Booking
            </a>
        </div>
        
        <h4>Test 2: JavaScript Navigation</h4>
        <button onclick="testJSNavigation()" class="btn btn-warning">Test JS Navigation</button>
        
        <h4>Test 3: Form POST Method</h4>
        <form method="POST" action="edit_booking.php" style="display: inline;">
            <input type="hidden" name="id" value="<?php echo $test_booking['id']; ?>">
            <button type="submit" class="btn btn-success">Form Submit Edit</button>
        </form>
        
        <h4>Test 4: New Window/Tab</h4>
        <a href="edit_booking.php?id=<?php echo $test_booking['id']; ?>" target="_blank" class="btn btn-info">
            Open Edit in New Tab
        </a>
    </div>

    <div class="test-section">
        <h3>📊 Real-Time Click Monitoring</h3>
        <div id="clickLog" class="log">
            <strong>Click events will appear here...</strong>
        </div>
        <button onclick="clearLog()" class="btn btn-sm btn-secondary">Clear Log</button>
    </div>

    <div class="test-section">
        <h3>🌐 URL Tests</h3>
        
        <h4>Direct URL Access Tests:</h4>
        <ul>
            <li><a href="../admin/edit_booking.php?id=<?php echo $test_booking['id']; ?>" target="_blank">
                ../admin/edit_booking.php?id=<?php echo $test_booking['id']; ?>
            </a></li>
            <li><a href="https://shazwan-danial.my/wedding-management-system/admin/edit_booking.php?id=<?php echo $test_booking['id']; ?>" target="_blank">
                Full URL Test
            </a></li>
        </ul>
    </div>

    <div class="test-section">
        <h3>🔍 Session & Auth Test</h3>
        <div class="log">
            <strong>Session Status:</strong><br>
            User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?><br>
            User Role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?><br>
            Is Logged In: <?php echo isLoggedIn() ? 'Yes' : 'No'; ?><br>
            Is Admin: <?php echo (getUserRole() === 'admin') ? 'Yes' : 'No'; ?><br>
        </div>
    </div>

    <?php else: ?>
    <div class="alert alert-warning">
        <h3>⚠️ No Bookings Found</h3>
        <p>No bookings available in database for testing. Please create a booking first.</p>
    </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let clickCount = 0;

function log(message, type = 'info') {
    clickCount++;
    const timestamp = new Date().toLocaleTimeString();
    const logDiv = document.getElementById('clickLog');
    const logClass = type === 'error' ? 'error' : type === 'success' ? 'success' : 'warning';
    
    logDiv.innerHTML += `
        <div class="${logClass}" style="margin: 5px 0; padding: 8px; border-radius: 3px;">
            <strong>[${clickCount}] ${timestamp}:</strong> ${message}
        </div>
    `;
    logDiv.scrollTop = logDiv.scrollHeight;
}

function clearLog() {
    document.getElementById('clickLog').innerHTML = '<strong>Click events will appear here...</strong>';
    clickCount = 0;
}

function testJSNavigation() {
    log('Testing JavaScript navigation...', 'info');
    const testId = <?php echo $test_booking['id'] ?? 0; ?>;
    const url = `edit_booking.php?id=${testId}`;
    
    log(`Attempting to navigate to: ${url}`, 'info');
    
    try {
        window.location.href = url;
        log('Navigation command executed', 'success');
    } catch (error) {
        log(`Navigation error: ${error.message}`, 'error');
    }
}

// Monitor ALL clicks on the page
$(document).ready(function() {
    log('Page loaded and monitoring started', 'success');
    
    // Monitor all link clicks
    $('a').on('click', function(e) {
        const href = $(this).attr('href') || 'No href';
        const target = $(this).attr('target') || 'same window';
        
        log(`Link clicked: ${href} (target: ${target})`, 'info');
        
        // For edit_booking.php links, add extra monitoring
        if (href.includes('edit_booking.php')) {
            log('🎯 EDIT BOOKING LINK DETECTED!', 'warning');
            log('Checking if navigation will work...', 'info');
            
            // Don't prevent default - let it navigate
            setTimeout(() => {
                log('If you see this message, the navigation was prevented somehow', 'error');
            }, 100);
        }
    });
    
    // Monitor page unload (navigation away)
    $(window).on('beforeunload', function() {
        console.log('Page is navigating away - SUCCESS!');
    });
    
    // Monitor for any JavaScript errors
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        log(`JavaScript Error: ${msg} at ${url}:${lineNo}`, 'error');
        return false;
    };
    
    // Check if we're being returned from edit_booking.php
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('returned')) {
        log('🔄 RETURNED from edit_booking.php - This means redirect occurred!', 'error');
    }
});
</script>

</body>
</html>