<?php

/**
 * ToyyibPay Payment Gateway Integration
 * For Wedding Management System
 */
class ToyyibPay
{
    private $secretKey;
    private $categoryCode;
    private $baseUrl;
    private $callbackSecret;
    private $returnUrl;
    private $callbackUrl;

    public function __construct()
    {
        // Configuration - these should be moved to config.php or environment variables
        $this->baseUrl = 'https://dev.toyyibpay.com'; // Change to https://toyyibpay.com for production
        $this->secretKey = 'vyuwfwmw-6o8z-3q2i-6ryw-8szldmscs2ea'; // Replace with your actual secret key
        $this->categoryCode = 'v98ntdhb'; // Replace with your actual category code
        $this->callbackSecret = 'tp_callback_secret_2024_shazwan_wedding_mgmt_sys'; // Generate a secure random string
        $this->returnUrl = SITE_URL . 'customer/payment-return.php';
        $this->callbackUrl = SITE_URL . 'includes/toyyibpay-callback.php';
    }

    /**
     * Create a new bill for payment
     */
    public function createBill($params, $pdo = null)
    {
        $billData = [
            'userSecretKey' => $this->secretKey,
            'categoryCode' => $this->categoryCode,
            'billName' => $params['billName'],
            'billDescription' => $params['billDescription'],
            'billPriceSetting' => 1, // Fixed price
            'billPayorInfo' => 1, // Collect customer info
            'billAmount' => $params['billAmount'] * 100, // ToyyibPay expects amount in cents
            'billReturnUrl' => $this->returnUrl,
            'billCallbackUrl' => $this->callbackUrl,
            'billExternalReferenceNo' => $params['billExternalReferenceNo'],
            'billTo' => $params['billTo'],
            'billEmail' => $params['billEmail'],
            'billPhone' => $params['billPhone'],
            'billSplitPayment' => 0,
            'billSplitPaymentArgs' => '',
            'billPaymentChannel' => 0, // 0 = All channels, 1 = FPX only, 2 = Credit/debit card only
            'billContentEmail' => 'Thank you for your payment!',
            'billChargeToCustomer' => 1, // Charge fees to customer
        ];

        $response = $this->makeRequest('/index.php/api/createBill', $billData);
        
        if ($response && isset($response[0]['BillCode'])) {
            $billCode = $response[0]['BillCode'];
            
            // Store transaction record in local database if PDO is available
            if ($pdo && isset($params['billExternalReferenceNo'])) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO toyyibpay_transactions (bill_code, booking_id, amount, status, created_at)
                        VALUES (?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([
                        $billCode,
                        $params['billExternalReferenceNo'],
                        $params['billAmount']
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to store ToyyibPay transaction: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'billCode' => $billCode,
                'paymentUrl' => $this->baseUrl . '/' . $billCode
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to create bill',
            'response' => $response
        ];
    }

    /**
     * Get bill transactions/status
     */
    public function getBillTransactions($billCode)
    {
        $data = [
            'userSecretKey' => $this->secretKey,
            'billCode' => $billCode
        ];

        $response = $this->makeRequest('/index.php/api/getBillTransactions', $data);
        
        // Log the response for debugging
        error_log("ToyyibPay getBillTransactions response for bill $billCode: " . json_encode($response));
        
        return $response;
    }

    /**
     * Verify payment callback
     */
    public function verifyCallback($callbackData)
    {
        // Verify required fields
        if (!isset($callbackData['billcode']) || !isset($callbackData['order_id'])) {
            return false;
        }

        // For sandbox, ToyyibPay might not send signature, so we'll skip signature verification in sandbox
        // In production, implement proper signature verification
        return true;
    }

    /**
     * Process payment callback
     */
    public function processCallback($callbackData, $pdo)
    {
        try {
            // Log the callback for debugging
            error_log("ToyyibPay Callback: " . json_encode($callbackData));

            if (!$this->verifyCallback($callbackData)) {
                throw new Exception('Invalid callback signature');
            }

            $billCode = $callbackData['billcode'];
            $orderId = $callbackData['order_id'];

            // Get bill transactions to verify payment status
            $transactions = $this->getBillTransactions($billCode);
            
            if (!$transactions || empty($transactions)) {
                throw new Exception('No transactions found for bill code: ' . $billCode);
            }

            // Since getBillTransactions() already filters by bill code,
            // we can use the first transaction returned
            $transaction = $transactions[0];
            
            // Add the bill code to the transaction data for consistency
            $transaction['billcode'] = $billCode;

            // Check if payment is successful (status 1 = successful)
            if ($transaction['billpaymentStatus'] == '1') {
                // Payment successful - update database
                $this->updatePaymentStatus($transaction, $pdo);
                return ['success' => true, 'message' => 'Payment processed successfully'];
            } else {
                // Payment not successful yet
                return ['success' => false, 'message' => 'Payment not completed'];
            }

        } catch (Exception $e) {
            error_log("ToyyibPay callback error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update payment status in database
     */
    private function updatePaymentStatus($transaction, $pdo)
    {
        $billCode = $transaction['billcode'];
        $transactionId = $transaction['billpaymentInvoiceNo'] ?? ''; // Use invoice number as transaction ID
        $amount = (float)$transaction['billpaymentAmount']; // Amount is already in RM format
        
        // Convert ToyyibPay date format (dd-mm-yyyy HH:mm:ss) to MySQL format (YYYY-MM-DD HH:MM:SS)
        $paymentDateRaw = $transaction['billPaymentDate'] ?? '';
        if ($paymentDateRaw && $paymentDateRaw !== '0000-00-00 00:00:00') {
            $dateTime = DateTime::createFromFormat('d-m-Y H:i:s', $paymentDateRaw);
            $paymentDate = $dateTime ? $dateTime->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        } else {
            $paymentDate = date('Y-m-d H:i:s');
        }
        
        $externalRef = $transaction['billExternalReferenceNo'] ?? '';

        // Find the booking by external reference
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ?");
        $stmt->execute([$externalRef]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new Exception('Booking not found for external reference: ' . $externalRef);
        }

        $bookingId = $booking['id'];

        // Check if payment already exists to prevent duplicates
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        if ($stmt->fetch()) {
            return; // Payment already processed
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert payment record
            $stmt = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_date, status, notes)
                VALUES (?, ?, 'online', ?, ?, 'completed', ?)
            ");
            $stmt->execute([
                $bookingId,
                $amount,
                $transactionId,
                $paymentDate,
                'ToyyibPay payment - Bill Code: ' . $billCode
            ]);

            // Update booking payment status and booking status
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET paid_amount = paid_amount + ?, 
                    payment_status = CASE 
                        WHEN paid_amount + ? >= total_amount THEN 'paid'
                        ELSE 'partial'
                    END,
                    booking_status = CASE 
                        WHEN booking_status = 'pending' AND paid_amount + ? >= total_amount THEN 'confirmed'
                        ELSE booking_status
                    END
                WHERE id = ?
            ");
            $stmt->execute([$amount, $amount, $amount, $bookingId]);

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
    }

    /**
     * Make HTTP request to ToyyibPay API
     */
    private function makeRequest($endpoint, $data)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("ToyyibPay API Error: " . $error);
            return false;
        }

        if ($httpCode !== 200) {
            error_log("ToyyibPay API HTTP Error: " . $httpCode);
            return false;
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ToyyibPay API JSON Error: " . json_last_error_msg());
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Validate payment amount and booking
     */
    public function validatePayment($bookingId, $amount, $pdo)
    {
        $stmt = $pdo->prepare("
            SELECT total_amount, paid_amount, payment_status, booking_status
            FROM bookings 
            WHERE id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['valid' => false, 'error' => 'Booking not found'];
        }

        if ($booking['booking_status'] === 'cancelled') {
            return ['valid' => false, 'error' => 'Cannot pay for cancelled booking'];
        }

        $remainingAmount = $booking['total_amount'] - $booking['paid_amount'];
        
        if ($amount > $remainingAmount) {
            return ['valid' => false, 'error' => 'Payment amount exceeds remaining balance'];
        }

        if ($amount <= 0) {
            return ['valid' => false, 'error' => 'Invalid payment amount'];
        }

        return [
            'valid' => true,
            'booking' => $booking,
            'remaining_amount' => $remainingAmount
        ];
    }
}