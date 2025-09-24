<?php
/**
 * Simple Callback Receiver - logs all incoming requests
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$log_file = '/tmp/toyyibpay_callback_log.txt';

// Log function
function logCallback($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

// Log that we received a request
logCallback("=== NEW REQUEST ===");
logCallback("Method: " . $_SERVER['REQUEST_METHOD']);
logCallback("Headers: " . json_encode(getallheaders() ?: []));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logCallback("POST Data: " . json_encode($_POST));
    logCallback("Raw Input: " . file_get_contents('php://input'));
    
    // Basic processing
    if (!empty($_POST['billcode'])) {
        $billcode = $_POST['billcode'];
        logCallback("Processing billcode: $billcode");
        
        // Try to include our actual callback processing
        try {
            require_once 'config.php';
            require_once 'toyyibpay.php';
            
            $toyyibpay = new ToyyibPay();
            $result = $toyyibpay->processCallback($_POST, $pdo);
            
            logCallback("Callback result: " . json_encode($result));
            
            if ($result['success']) {
                logCallback("SUCCESS: Callback processed successfully");
                echo 'OK';
            } else {
                logCallback("ERROR: " . ($result['message'] ?? 'Unknown error'));
                echo 'ERROR';
            }
            
        } catch (Exception $e) {
            logCallback("EXCEPTION: " . $e->getMessage());
            logCallback("Stack trace: " . $e->getTraceAsString());
            echo 'ERROR';
        }
    } else {
        logCallback("ERROR: No billcode in POST data");
        echo 'ERROR: No billcode';
    }
} else {
    logCallback("Non-POST request received");
    http_response_code(405);
    echo 'Method not allowed';
}

logCallback("Response sent, request complete");
logCallback("=== END REQUEST ===\n");
?>