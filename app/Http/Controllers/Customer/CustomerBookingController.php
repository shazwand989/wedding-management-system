<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\WeddingPackage;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerBookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['package', 'vendors'])
            ->where('customer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('customer.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $packages = WeddingPackage::where('status', 'active')->get();
        $vendors = Vendor::where('status', 'active')->with('user')->get();

        return view('customer.bookings.create', compact('packages', 'vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'nullable|exists:wedding_packages,id',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string',
            'guest_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'special_requests' => 'nullable|string',
            'vendors' => 'nullable|array',
            'vendors.*' => 'exists:vendors,id',
        ]);

        $booking = Booking::create([
            'customer_id' => Auth::id(),
            'package_id' => $validated['package_id'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'venue_name' => $validated['venue_name'],
            'venue_address' => $validated['venue_address'],
            'guest_count' => $validated['guest_count'],
            'total_amount' => $validated['total_amount'],
            'booking_status' => 'pending',
            'notes' => $validated['special_requests'] ?? null,
        ]);

        // Attach vendors if selected
        if (!empty($validated['vendors'])) {
            foreach ($validated['vendors'] as $vendorId) {
                $booking->vendors()->attach($vendorId, [
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('customer.payment.show', $booking->id)
            ->with('success', 'Booking created successfully! Please proceed with payment.');
    }

    public function show($id)
    {
        $booking = Booking::with(['package', 'vendors.user', 'payments'])
            ->where('customer_id', Auth::id())
            ->findOrFail($id);

        return view('customer.bookings.show', compact('booking'));
    }
}
