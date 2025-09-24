<?php
/**
 * ToyyibPay Callback Handler
 * Processes payment notifications from ToyyibPay
 */

require_once 'config.php';
require_once 'toyyibpay.php';

// Set content type for response
header('Content-Type: text/plain');

// Log all incoming requests
error_log("ToyyibPay Callback received: " . json_encode($_POST));

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Method not allowed';
        exit;
    }

    // Get callback data
    $callbackData = $_POST;

    // Validate required fields
    if (empty($callbackData['billcode'])) {
        http_response_code(400);
        echo 'Missing bill code';
        exit;
    }

    // Create ToyyibPay instance
    $toyyibpay = new ToyyibPay();

    // Process the callback with database locking to prevent race conditions
    $billCode = $callbackData['billcode'];
    $lockKey = 'toyyibpay_' . $billCode;

    // Simple file-based locking mechanism
    $lockFile = sys_get_temp_dir() . '/' . $lockKey . '.lock';
    $lockHandle = fopen($lockFile, 'w');

    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        // Another process is already handling this callback
        echo 'OK'; // Return OK to prevent retries
        fclose($lockHandle);
        exit;
    }

    try {
        // Process the callback
        $result = $toyyibpay->processCallback($callbackData, $pdo);

        if ($result['success']) {
            echo 'OK';
        } else {
            error_log("ToyyibPay callback processing failed: " . $result['message']);
            echo 'ERROR: ' . $result['message'];
        }

    } finally {
        // Release the lock
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        unlink($lockFile);
    }

} catch (Exception $e) {
    error_log("ToyyibPay callback exception: " . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}