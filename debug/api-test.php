<?php
require_once '../includes/config.php';
require_once '../includes/toyyibpay.php';

header('Content-Type: application/json');

$bill_code = $_GET['bill_code'] ?? '';

if (empty($bill_code)) {
    echo json_encode(['error' => 'No bill code provided']);
    exit;
}

try {
    $toyyibpay = new ToyyibPay();
    $response = $toyyibpay->getBillTransactions($bill_code);
    
    // Return the raw response so we can see the structure
    echo json_encode([
        'success' => true,
        'bill_code' => $bill_code,
        'raw_response' => $response,
        'response_type' => gettype($response),
        'response_keys' => is_array($response) ? array_keys($response) : 'not_array'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>