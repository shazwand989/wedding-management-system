<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ToyyibPayService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    protected $toyyibpayService;

    public function __construct(ToyyibPayService $toyyibpayService)
    {
        $this->toyyibpayService = $toyyibpayService;
    }

    public function show($bookingId)
    {
        $booking = Booking::with('package')->findOrFail($bookingId);

        // Check if booking belongs to current customer
        if ($booking->customer_id !== auth()->id()) {
            abort(403);
        }

        $remainingAmount = $booking->total_amount - $booking->paid_amount;

        return view('customer.payment', compact('booking', 'remainingAmount'));
    }

    public function process(Request $request, $bookingId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $booking = Booking::findOrFail($bookingId);

        // Check if booking belongs to current customer
        if ($booking->customer_id !== auth()->id()) {
            abort(403);
        }

        // Validate payment
        $validation = $this->toyyibpayService->validatePayment($bookingId, $validated['amount']);

        if (!$validation['valid']) {
            return back()->withErrors(['amount' => $validation['error']]);
        }

        // Create ToyyibPay bill
        $billParams = [
            'billName' => 'Wedding Booking Payment - #' . $booking->id,
            'billDescription' => 'Payment for wedding booking on ' . $booking->event_date->format('d M Y'),
            'billAmount' => $validated['amount'],
            'billExternalReferenceNo' => $booking->id,
            'billTo' => auth()->user()->full_name,
            'billEmail' => auth()->user()->email,
            'billPhone' => auth()->user()->phone ?? '',
        ];

        $result = $this->toyyibpayService->createBill($billParams);

        if ($result['success']) {
            return redirect($result['paymentUrl']);
        }

        return back()->withErrors(['payment' => 'Failed to create payment. Please try again.']);
    }

    public function return(Request $request)
    {
        $status = $request->query('status_id');
        $billCode = $request->query('billcode');
        $orderId = $request->query('order_id');

        if ($status == 1) {
            // Payment successful
            return redirect()->route('customer.bookings.show', $orderId)
                           ->with('success', 'Payment completed successfully!');
        }

        // Payment failed or cancelled
        return redirect()->route('customer.dashboard')
                       ->with('error', 'Payment was not completed.');
    }
}
