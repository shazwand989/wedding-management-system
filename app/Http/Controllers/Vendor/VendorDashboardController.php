<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    public function index()
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        // Get vendor's bookings through pivot table
        $totalBookings = DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->count();

        $confirmedBookings = DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->where('status', 'confirmed')
            ->count();

        $pendingBookings = DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->count();

        $totalEarnings = DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->where('status', 'confirmed')
            ->sum('agreed_price');

        // Get upcoming bookings
        $upcomingBookings = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->where('bookings.event_date', '>=', now())
            ->select(
                'bookings.*',
                'users.name as customer_name',
                'booking_vendors.agreed_price',
                'booking_vendors.status as vendor_status'
            )
            ->orderBy('bookings.event_date', 'asc')
            ->limit(5)
            ->get();

        // Get recent booking requests
        $recentRequests = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'pending')
            ->select(
                'bookings.*',
                'users.name as customer_name',
                'booking_vendors.id as booking_vendor_id',
                'booking_vendors.status as vendor_status'
            )
            ->orderBy('booking_vendors.created_at', 'desc')
            ->limit(5)
            ->get();

        return view('vendor.dashboard', compact(
            'vendor',
            'totalBookings',
            'confirmedBookings',
            'pendingBookings',
            'totalEarnings',
            'upcomingBookings',
            'recentRequests'
        ));
    }

    public function calendar()
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        // Get all confirmed bookings for calendar view
        $bookings = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->select(
                'bookings.*',
                'users.name as customer_name',
                'booking_vendors.agreed_price'
            )
            ->get();

        return view('vendor.calendar', compact('bookings'));
    }
}
