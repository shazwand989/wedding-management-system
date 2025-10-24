<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorEarningsController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        // Date range
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Total earnings
        $totalEarnings = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->whereBetween('bookings.event_date', [$startDate, $endDate])
            ->sum('booking_vendors.agreed_price');

        // Pending earnings
        $pendingEarnings = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'pending')
            ->whereBetween('bookings.event_date', [$startDate, $endDate])
            ->sum('booking_vendors.agreed_price');

        // Completed bookings count
        $completedCount = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->where('bookings.booking_status', 'completed')
            ->whereBetween('bookings.event_date', [$startDate, $endDate])
            ->count();

        // Earnings by month (last 6 months)
        $earningsByMonth = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->where('bookings.event_date', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(bookings.event_date, "%Y-%m") as month'),
                DB::raw('SUM(booking_vendors.agreed_price) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Detailed earnings list
        $earnings = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('booking_vendors.status', 'confirmed')
            ->whereBetween('bookings.event_date', [$startDate, $endDate])
            ->select(
                'bookings.id',
                'bookings.event_date',
                'bookings.venue_name',
                'bookings.booking_status',
                'users.name as customer_name',
                'booking_vendors.agreed_price',
                'booking_vendors.created_at'
            )
            ->orderBy('bookings.event_date', 'desc')
            ->paginate(15);

        return view('vendor.earnings.index', compact(
            'totalEarnings',
            'pendingEarnings',
            'completedCount',
            'earningsByMonth',
            'earnings',
            'startDate',
            'endDate'
        ));
    }
}
