<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\WeddingPackage;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'package']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('booking_status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = Booking::with(['customer', 'package', 'vendors'])->findOrFail($id);

        return view('admin.bookings.show', compact('booking'));
    }

    public function edit($id)
    {
        $booking = Booking::with(['customer', 'package'])->findOrFail($id);
        $packages = WeddingPackage::where('status', 'active')->get();
        $customers = User::where('role', 'customer')->where('status', 'active')->get();

        return view('admin.bookings.edit', compact('booking', 'packages', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'package_id' => 'nullable|exists:wedding_packages,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string',
            'guest_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'booking_status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $booking->update($validated);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully');
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully');
    }
}
