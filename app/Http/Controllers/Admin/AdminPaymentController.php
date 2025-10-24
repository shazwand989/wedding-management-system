<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.customer']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }

        // Search by transaction ID or customer
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('booking.customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calculate totals
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $pendingRevenue = Payment::where('status', 'pending')->sum('amount');

        return view('admin.payments.index', compact('payments', 'totalRevenue', 'pendingRevenue'));
    }

    public function show($id)
    {
        $payment = Payment::with(['booking.customer', 'booking.package'])->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }
}
