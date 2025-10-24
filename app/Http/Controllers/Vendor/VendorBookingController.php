<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorBookingController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        $query = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('booking_vendors.status', $request->status);
        }

        $bookings = $query->select(
                'bookings.*',
                'users.name as customer_name',
                'users.phone as customer_phone',
                'users.email as customer_email',
                'booking_vendors.id as booking_vendor_id',
                'booking_vendors.status as vendor_status',
                'booking_vendors.agreed_price',
                'booking_vendors.notes as vendor_notes'
            )
            ->orderBy('bookings.event_date', 'desc')
            ->paginate(15);

        return view('vendor.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        $booking = DB::table('booking_vendors')
            ->join('bookings', 'booking_vendors.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.customer_id', '=', 'users.id')
            ->where('booking_vendors.vendor_id', $vendor->id)
            ->where('bookings.id', $id)
            ->select(
                'bookings.*',
                'users.name as customer_name',
                'users.phone as customer_phone',
                'users.email as customer_email',
                'users.address as customer_address',
                'booking_vendors.id as booking_vendor_id',
                'booking_vendors.status as vendor_status',
                'booking_vendors.agreed_price',
                'booking_vendors.notes as vendor_notes'
            )
            ->firstOrFail();

        return view('vendor.bookings.show', compact('booking'));
    }

    public function accept(Request $request, $id)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'agreed_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->where('booking_id', $id)
            ->update([
                'status' => 'confirmed',
                'agreed_price' => $validated['agreed_price'],
                'notes' => $validated['notes'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->route('vendor.bookings.index')
            ->with('success', 'Booking accepted successfully');
    }

    public function reject(Request $request, $id)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        DB::table('booking_vendors')
            ->where('vendor_id', $vendor->id)
            ->where('booking_id', $id)
            ->update([
                'status' => 'rejected',
                'notes' => $validated['notes'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->route('vendor.bookings.index')
            ->with('success', 'Booking rejected');
    }
}
