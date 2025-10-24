<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\WeddingPackage;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $customerId = auth()->id();

        // Get customer's bookings
        $bookings = Booking::with(['package'])
                          ->where('customer_id', $customerId)
                          ->latest()
                          ->get();

        // Get upcoming booking
        $upcomingBooking = Booking::with('package')
                                  ->where('customer_id', $customerId)
                                  ->where('event_date', '>=', now())
                                  ->whereIn('booking_status', ['pending', 'confirmed'])
                                  ->orderBy('event_date', 'asc')
                                  ->first();

        // Calculate stats
        $totalSpent = $bookings->where('booking_status', 'completed')->sum('total_amount');
        $totalBookings = $bookings->count();
        $completedBookings = $bookings->where('booking_status', 'completed')->count();

        // Get available packages
        $packages = WeddingPackage::where('status', 'active')
                                  ->orderBy('price', 'asc')
                                  ->limit(3)
                                  ->get();

        return view('customer.dashboard', compact(
            'bookings',
            'upcomingBooking',
            'totalSpent',
            'totalBookings',
            'completedBookings',
            'packages'
        ));
    }

    public function profile()
    {
        $user = auth()->user();
        return view('customer.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        auth()->user()->update($validated);

        return redirect()->route('customer.profile')
                        ->with('success', 'Profile updated successfully!');
    }
}
