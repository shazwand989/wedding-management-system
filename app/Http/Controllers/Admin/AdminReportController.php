<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        // Date range
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Revenue statistics
        $totalRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $pendingPayments = Payment::where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Booking statistics
        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedBookings = Booking::where('booking_status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $cancelledBookings = Booking::where('booking_status', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Revenue by month (last 6 months)
        $revenueByMonth = Payment::where('status', 'completed')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Bookings by status
        $bookingsByStatus = Booking::select('booking_status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('booking_status')
            ->get();

        // Top packages
        $topPackages = Booking::with('package')
            ->select('package_id', DB::raw('COUNT(*) as booking_count'), DB::raw('SUM(total_amount) as revenue'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('package_id')
            ->groupBy('package_id')
            ->orderBy('booking_count', 'desc')
            ->limit(5)
            ->get();

        // Active vendors by service type
        $vendorsByType = Vendor::where('status', 'active')
            ->select('service_type', DB::raw('COUNT(*) as count'))
            ->groupBy('service_type')
            ->get();

        return view('admin.reports.index', compact(
            'startDate',
            'endDate',
            'totalRevenue',
            'pendingPayments',
            'totalBookings',
            'completedBookings',
            'cancelledBookings',
            'revenueByMonth',
            'bookingsByStatus',
            'topPackages',
            'vendorsByType'
        ));
    }
}
