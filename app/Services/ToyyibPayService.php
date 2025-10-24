<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\ToyyibpayTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToyyibPayService
{
    private $secretKey;
    private $categoryCode;
    private $baseUrl;
    private $callbackSecret;
    private $returnUrl;
    private $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.toyyibpay.base_url');
        $this->secretKey = config('services.toyyibpay.secret_key');
        $this->categoryCode = config('services.toyyibpay.category_code');
        $this->callbackSecret = config('services.toyyibpay.callback_secret');
        $this->returnUrl = route('customer.payment.return');
        $this->callbackUrl = route('toyyibpay.callback');
    }

    /**
     * Create a new bill for payment
     */
    public function createBill(array $params)
    {
        $billData = [
            'userSecretKey' => $this->secretKey,
            'categoryCode' => $this->categoryCode,
            'billName' => $params['billName'],
            'billDescription' => $params['billDescription'],
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => $params['billAmount'] * 100,
            'billReturnUrl' => $this->returnUrl,
            'billCallbackUrl' => $this->callbackUrl,
            'billExternalReferenceNo' => $params['billExternalReferenceNo'],
            'billTo' => $params['billTo'],
            'billEmail' => $params['billEmail'],
            'billPhone' => $params['billPhone'],
            'billSplitPayment' => 0,
            'billSplitPaymentArgs' => '',
            'billPaymentChannel' => 0,
            'billContentEmail' => 'Thank you for your payment!',
            'billChargeToCustomer' => 1,
        ];

        $response = $this->makeRequest('/index.php/api/createBill', $billData);

        if ($response && isset($response[0]['BillCode'])) {
            $billCode = $response[0]['BillCode'];

            // Store transaction record in database
            ToyyibpayTransaction::create([
                'bill_code' => $billCode,
                'booking_id' => $params['billExternalReferenceNo'],
                'amount' => $params['billAmount'],
                'status' => 'pending',
            ]);

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

        Log::info("ToyyibPay getBillTransactions response for bill $billCode", ['response' => $response]);

        return $response;
    }

    /**
     * Verify payment callback
     */
    public function verifyCallback(array $callbackData)
    {
        return isset($callbackData['billcode']) && isset($callbackData['order_id']);
    }

    /**
     * Process payment callback
     */
    public function processCallback(array $callbackData)
    {
        try {
            Log::info("ToyyibPay Callback received", $callbackData);

            if (!$this->verifyCallback($callbackData)) {
                throw new Exception('Invalid callback data');
            }

            $billCode = $callbackData['billcode'];
            $orderId = $callbackData['order_id'];

            $transactions = $this->getBillTransactions($billCode);

            if (!$transactions || empty($transactions)) {
                throw new Exception('No transactions found for bill code: ' . $billCode);
            }

            $transaction = $transactions[0];
            $transaction['billcode'] = $billCode;

            if ($transaction['billpaymentStatus'] == '1') {
                $this->updatePaymentStatus($transaction);
                return ['success' => true, 'message' => 'Payment processed successfully'];
            }

            return ['success' => false, 'message' => 'Payment not completed'];

        } catch (Exception $e) {
            Log::error("ToyyibPay callback error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update payment status in database
     */
    private function updatePaymentStatus(array $transaction)
    {
        $billCode = $transaction['billcode'];
        $transactionId = $transaction['billpaymentInvoiceNo'] ?? '';
        $amount = (float)$transaction['billpaymentAmount'];

        $paymentDateRaw = $transaction['billPaymentDate'] ?? '';
        if ($paymentDateRaw && $paymentDateRaw !== '0000-00-00 00:00:00') {
            $dateTime = \DateTime::createFromFormat('d-m-Y H:i:s', $paymentDateRaw);
            $paymentDate = $dateTime ? $dateTime->format('Y-m-d') : now()->format('Y-m-d');
        } else {
            $paymentDate = now()->format('Y-m-d');
        }

        $externalRef = $transaction['billExternalReferenceNo'] ?? '';

        $booking = Booking::find($externalRef);

        if (!$booking) {
            throw new Exception('Booking not found for external reference: ' . $externalRef);
        }

        // Check if payment already exists
        if (Payment::where('transaction_id', $transactionId)->exists()) {
            return;
        }

        DB::transaction(function () use ($booking, $amount, $transactionId, $paymentDate, $billCode) {
            // Create payment record
            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $amount,
                'payment_method' => 'online',
                'transaction_id' => $transactionId,
                'payment_date' => $paymentDate,
                'status' => 'completed',
                'notes' => 'ToyyibPay payment - Bill Code: ' . $billCode,
            ]);

            // Update booking
            $newPaidAmount = $booking->paid_amount + $amount;
            $paymentStatus = $newPaidAmount >= $booking->total_amount ? 'paid' : 'partial';
            $bookingStatus = $booking->booking_status === 'pending' && $paymentStatus === 'paid'
                            ? 'confirmed'
                            : $booking->booking_status;

            $booking->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
                'booking_status' => $bookingStatus,
            ]);

            // Update ToyyibPay transaction
            ToyyibpayTransaction::where('bill_code', $billCode)
                ->update(['status' => 'success']);
        });
    }

    /**
     * Make HTTP request to ToyyibPay API
     */
    private function makeRequest(string $endpoint, array $data)
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("ToyyibPay API Error: " . $error);
            return false;
        }

        if ($httpCode !== 200) {
            Log::error("ToyyibPay API HTTP Error: " . $httpCode);
            return false;
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("ToyyibPay API JSON Error: " . json_last_error_msg());
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Validate payment amount and booking
     */
    public function validatePayment($bookingId, $amount)
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return ['valid' => false, 'error' => 'Booking not found'];
        }

        if ($booking->booking_status === 'cancelled') {
            return ['valid' => false, 'error' => 'Cannot pay for cancelled booking'];
        }

        $remainingAmount = $booking->total_amount - $booking->paid_amount;

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
