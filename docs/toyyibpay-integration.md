# ToyyibPay Integration Guide

Integrate ToyyibPay into the wedding management system (or any PHP/Laravel project) with a secure, testable workflow. This guide covers API key setup, bill creation, payment redirects, callback validation, duplicate prevention, and sandbox-to-production migration.

---

## Prerequisites

- ToyyibPay merchant account (sandbox + production). Register at <https://dev.toyyibpay.com> for sandbox.
- A secret key (`userSecretKey`) and at least one category code generated from the ToyyibPay dashboard.
- HTTPS-accessible URLs for payment return and callback endpoints during production.
- PHP 8.0+ recommended, with Composer if using Laravel/Guzzle HTTP client.

---

## Environment Configuration

Store secrets in environment variables; never hardcode in source control.

```dotenv
# .env
TOYYIBPAY_BASE_URL=https://dev.toyyibpay.com
TOYYIBPAY_SECRET_KEY=your_sandbox_secret_key
TOYYIBPAY_CATEGORY_CODE=your_sandbox_category_code
TOYYIBPAY_RETURN_URL=https://example.test/payments/toyyibpay/return
TOYYIBPAY_CALLBACK_URL=https://example.test/payments/toyyibpay/callback
TOYYIBPAY_CALLBACK_SECRET=super-secure-random-string
```

When promoting to production, switch `TOYYIBPAY_BASE_URL` to `https://toyyibpay.com` and replace keys/URLs.

---

## Payment Flow Overview

1. **Create Bill** – Server-side call to `/index.php/api/createBill` with amount, customer data, return URL, callback URL, and metadata.
2. **Redirect Customer** – Send customer to the returned `billCode` URL for payment.
3. **Callback Handling** – ToyyibPay calls your callback URL with payment status.
4. **Verification** – Fetch bill status via API to confirm and prevent tampering.
5. **Finalize Order** – Mark booking as paid, persist transaction reference, and avoid duplicates.

---

## Generic PHP Example

Requires `guzzlehttp/guzzle` (Composer).

```bash
composer require guzzlehttp/guzzle
```

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ToyyibPayClient
{
    private Client $http;
    private string $secretKey;
    private string $categoryCode;
    private string $callbackSecret;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => getenv('TOYYIBPAY_BASE_URL') ?: 'https://dev.toyyibpay.com',
            'timeout'  => 10,
        ]);

        $this->secretKey      = getenv('TOYYIBPAY_SECRET_KEY');
        $this->categoryCode   = getenv('TOYYIBPAY_CATEGORY_CODE');
        $this->callbackSecret = getenv('TOYYIBPAY_CALLBACK_SECRET');
    }

    public function createBill(array $payload): array
    {
        $data = array_merge([
            'userSecretKey' => $this->secretKey,
            'categoryCode'  => $this->categoryCode,
            'billName'      => 'Wedding Package',
            'billDescription' => 'Booking payment',
            'billAmount'    => (int) round($payload['amount'] * 100), // ToyyibPay expects cents
            'billReturnUrl' => getenv('TOYYIBPAY_RETURN_URL'),
            'billCallbackUrl' => getenv('TOYYIBPAY_CALLBACK_URL'),
            'billExternalReferenceNo' => $payload['reference'],
        ], $payload['customer']);

        try {
            $response = $this->http->post('/index.php/api/createBill', ['form_params' => $data]);
            $json = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (!empty($json['status']) && $json['status'] === 'success' && !empty($json[0]['BillCode'])) {
                return [
                    'bill_code' => $json[0]['BillCode'],
                    'payment_url' => sprintf('%s/%s', rtrim(getenv('TOYYIBPAY_BASE_URL'), '/'), $json[0]['BillCode']),
                ];
            }

            throw new RuntimeException('ToyyibPay did not return a bill code. Response: ' . json_encode($json));
        } catch (GuzzleException | JsonException $exception) {
            throw new RuntimeException('ToyyibPay createBill failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function verifyBill(string $billCode): array
    {
        try {
            $response = $this->http->post('/index.php/api/getBillTransactions', [
                'form_params' => [
                    'userSecretKey' => $this->secretKey,
                    'billCode'      => $billCode,
                ],
            ]);

            return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException | JsonException $exception) {
            throw new RuntimeException('ToyyibPay getBillTransactions failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function validateCallback(array $payload): bool
    {
        if (!isset($payload['signature'], $payload['billcode'], $payload['order_id'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload['billcode'] . '|' . $payload['order_id'], $this->callbackSecret);
        return hash_equals($expected, $payload['signature']);
    }
}
```

```php
// callback-handler.php
require __DIR__ . '/bootstrap.php';

$client = new ToyyibPayClient();

$payload = $_POST;
if (!$client->validateCallback($payload)) {
    http_response_code(400);
    error_log('ToyyibPay callback signature mismatch: ' . json_encode($payload));
    exit('Invalid signature');
}

$billTransactions = $client->verifyBill($payload['billcode']);
// Ensure status is paid, match amount/reference, and mark order as paid idempotently.
```

### Preventing Duplicate Transactions

Design your `payments` table with unique constraints on `gateway_reference` or `bill_code` + `transaction_id`. Example schema:

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    bill_code VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    UNIQUE KEY unique_transaction (transaction_id),
    INDEX idx_order (order_id)
);
```

Before marking an order as paid, check if the transaction already exists; ignore duplicates with a warning log.

---

## Laravel Example

### 1. Environment

Update `.env` with the variables shown earlier.

### 2. Configuration

`config/services.php`

```php
' toyyibpay ' => [
    'base_url'        => env('TOYYIBPAY_BASE_URL', 'https://dev.toyyibpay.com'),
    'secret_key'      => env('TOYYIBPAY_SECRET_KEY'),
    'category_code'   => env('TOYYIBPAY_CATEGORY_CODE'),
    'return_url'      => env('TOYYIBPAY_RETURN_URL'),
    'callback_url'    => env('TOYYIBPAY_CALLBACK_URL'),
    'callback_secret' => env('TOYYIBPAY_CALLBACK_SECRET'),
],
```

### 3. Service Class

`app/Services/ToyyibPay/ToyyibPayService.php`

```php
<?php

namespace App\Services\ToyyibPay;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ToyyibPayService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('services.toyyibpay.base_url'),
            'timeout'  => 10,
        ]);
    }

    public function createBill(array $booking): array
    {
        $payload = [
            'userSecretKey' => config('services.toyyibpay.secret_key'),
            'categoryCode'  => config('services.toyyibpay.category_code'),
            'billName'      => $booking['title'],
            'billDescription' => $booking['description'] ?? 'Wedding booking',
            'billAmount'    => (int) round($booking['amount'] * 100),
            'billReturnUrl' => config('services.toyyibpay.return_url'),
            'billCallbackUrl' => config('services.toyyibpay.callback_url'),
            'billTo'        => $booking['customer']['name'],
            'billEmail'     => $booking['customer']['email'],
            'billPhone'     => $booking['customer']['phone'],
            'billExternalReferenceNo' => $booking['reference'],
        ];

        $response = $this->http->post('/index.php/api/createBill', ['form_params' => $payload]);
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($json[0]['BillCode'])) {
            Log::error('ToyyibPay createBill failure', ['payload' => $payload, 'response' => $json]);
            throw new RuntimeException('Unable to create ToyyibPay bill');
        }

        return [
            'bill_code'   => $json[0]['BillCode'],
            'payment_url' => sprintf('%s/%s', rtrim(config('services.toyyibpay.base_url'), '/'), $json[0]['BillCode']),
        ];
    }

    public function verifyBill(string $billCode): array
    {
        $response = $this->http->post('/index.php/api/getBillTransactions', [
            'form_params' => [
                'userSecretKey' => config('services.toyyibpay.secret_key'),
                'billCode'      => $billCode,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function validateSignature(array $payload): bool
    {
        $signature = Arr::get($payload, 'signature');
        $billCode  = Arr::get($payload, 'billcode');
        $orderId   = Arr::get($payload, 'order_id');

        if (!$signature || !$billCode || !$orderId) {
            return false;
        }

        $expected = hash_hmac('sha256', "$billCode|$orderId", config('services.toyyibpay.callback_secret'));
        return hash_equals($expected, $signature);
    }
}
```

### 4. Controller + Routes

`app/Http/Controllers/Payment/ToyyibPayController.php`

```php
<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ToyyibPay\ToyyibPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ToyyibPayController extends Controller
{
    public function __construct(private ToyyibPayService $service)
    {
    }

    public function createBill(Booking $booking)
    {
        $bill = $this->service->createBill([
            'reference' => $booking->reference,
            'amount'    => $booking->total_due,
            'title'     => $booking->package->name,
            'description' => $booking->package->description,
            'customer'  => [
                'name'  => $booking->customer->full_name,
                'email' => $booking->customer->email,
                'phone' => $booking->customer->phone,
            ],
        ]);

        Payment::create([
            'booking_id'   => $booking->id,
            'gateway'      => 'toyyibpay',
            'bill_code'    => $bill['bill_code'],
            'status'       => 'pending',
            'amount'       => $booking->total_due,
            'reference'    => Str::uuid(),
        ]);

        return redirect()->away($bill['payment_url']);
    }

    public function callback(Request $request): Response
    {
        $payload = $request->all();

        if (!$this->service->validateSignature($payload)) {
            Log::warning('ToyyibPay callback signature mismatch', $payload);
            return response('invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $billCode = $payload['billcode'];

        $lock = Cache::lock('toyyibpay:' . $billCode, 10);
        if (!$lock->get()) {
            return response('duplicate', Response::HTTP_OK);
        }

        try {
            $transactions = $this->service->verifyBill($billCode);
            $transaction = collect($transactions)->firstWhere('billcode', $billCode);

            if (!$transaction) {
                Log::warning('ToyyibPay transaction not found', ['billcode' => $billCode, 'transactions' => $transactions]);
                return response('not found', Response::HTTP_NOT_FOUND);
            }

            if ((int) $transaction['billpaymentStatus'] !== 1) {
                Log::info('ToyyibPay still pending', ['billcode' => $billCode]);
                return response('pending', Response::HTTP_OK);
            }

            Payment::updateOrCreate(
                ['transaction_id' => $transaction['billpaymentTransactionId'] ?? null],
                [
                    'bill_code'      => $billCode,
                    'status'         => 'paid',
                    'paid_at'        => now(),
                    'amount'         => $transaction['billpaymentAmount'] / 100,
                    'raw_response'   => $transactions,
                ]
            );

            $booking = Booking::where('reference', $transaction['billExternalReferenceNo'])->first();
            if ($booking && !$booking->is_paid) {
                $booking->markAsPaid();
            }

            return response('ok', Response::HTTP_OK);
        } finally {
            optional($lock)->release();
        }
    }
}
```

`routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/bookings/{booking}/pay', [ToyyibPayController::class, 'createBill'])->name('bookings.pay');
});

Route::post('/payments/toyyibpay/callback', [ToyyibPayController::class, 'callback'])
    ->name('payments.toyyibpay.callback');
```

### 5. Validation & Logging

- Log every API request/response (omit sensitive data) using Laravel channel `stack` or dedicated `toyyibpay` channel.
- Use queued jobs to retry verification if ToyyibPay is slow. Example: dispatch `VerifyToyyibPayPayment` job on callback to double-check status.
- Guard routes with CSRF disabled only for ToyyibPay callback (`VerifyCsrfToken` exception list).

---

## Security Best Practices

- Serve callback endpoints over HTTPS.
- Validate ToyyibPay signatures and the amount, currency, and reference against your records.
- Implement idempotency through DB locks or cache to block duplicate callbacks.
- Store raw webhook payloads for audit but redact personal data when logging.
- Use rate limiting or middleware to prevent DDoS on payment endpoints.
- Regularly rotate `TOYYIBPAY_CALLBACK_SECRET` and other keys; update ToyyibPay settings when doing so.

---

## Sandbox Testing Checklist

1. **Enable Sandbox** – Create account at <https://dev.toyyibpay.com> and log in.
2. **Generate Keys** – Obtain `userSecretKey` (profile) and create a category to get `categoryCode`.
3. **Configure URLs** – Set return + callback URLs in the ToyyibPay dashboard (use `ngrok` or similar for local testing).
4. **Funding Simulation** – Sandbox accepts test payments with FPX simulation or card placeholder values provided in ToyyibPay docs.
5. **Create Test Bills** – Use staging data; verify that bill amount matches expected invoice totals.
6. **Callback Validation** – Inspect request logs to ensure signature passes and duplicates are ignored.
7. **Check Status API** – Confirm `getBillTransactions` shows `billpaymentStatus = 1` on success.
8. **Reverse Scenarios** – Attempt canceled/failed payments and ensure your system remains pending/unpaid.

Document the entire flow with sample payloads before going live.

---

## Go-Live Steps

- Swap `.env` to production values and clear config cache (`php artisan config:clear`).
- Update ToyyibPay dashboard URLs to production domain.
- Run a full payment test with a small real amount; verify reconciliation with ToyyibPay settlement report.
- Monitor logs and database for unexpected duplicate entries or signature mismatches.
- Set up alerting on payment failures using Laravel notifications or external tools.

---

## Troubleshooting

| Issue | Recommended Action |
| --- | --- |
| 403/401 responses | Confirm `userSecretKey` is correct for the environment; check IP whitelisting if enabled. |
| Missing callbacks | Verify ToyyibPay has the correct callback URL and your server returns HTTP 200 within 30 seconds. |
| Wrong amount | Ensure amounts are multiplied by 100 (cents) before sending to ToyyibPay. |
| Duplicated records | Add unique constraints and acquire cache/database locks during callback processing. |

---

## Further Enhancements

- Implement scheduled command to reconcile recent bills each hour (`php artisan schedule:run`).
- Create UI within the admin dashboard to display ToyyibPay transactions with status badges.
- Store ToyyibPay settlement references to aid finance reconciliation.
- Add automated tests covering bill creation and callback handling using Laravel HTTP client fakes.
