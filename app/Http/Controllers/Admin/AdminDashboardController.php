<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Get dashboard statistics
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('booking_status', 'pending')->count();
        $totalRevenue = Booking::where('booking_status', 'completed')->sum('total_amount');
        $totalVendors = Vendor::where('status', 'active')->count();

        // Get recent bookings
        $recentBookings = Booking::with(['customer', 'package'])
                                 ->latest()
                                 ->limit(5)
                                 ->get();

        // Get pending vendor approvals
        $pendingVendors = Vendor::with('user')
                                ->where('status', 'pending')
                                ->latest()
                                ->get();

        return view('admin.dashboard', compact(
            'totalBookings',
            'pendingBookings',
            'totalRevenue',
            'totalVendors',
            'recentBookings',
            'pendingVendors'
        ));
    }

    public function customers()
    {
        $customers = User::where('role', 'customer')
                        ->with('bookings')
                        ->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function payments()
    {
        // Implementation for payments page
        return view('admin.payments.index');
    }

    public function reports()
    {
        // Implementation for reports page
        return view('admin.reports.index');
    }
}
