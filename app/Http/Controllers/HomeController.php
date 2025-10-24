<?php

namespace App\Http\Controllers;

use App\Models\WeddingPackage;
use App\Models\Vendor;
use App\Models\Booking;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Get wedding packages
        $packages = WeddingPackage::where('status', 'active')
                                  ->orderBy('price', 'asc')
                                  ->get();

        // Get statistics
        $vendorCount = Vendor::where('status', 'active')->count();
        $bookingCount = Booking::count();
        $customerCount = Booking::where('booking_status', 'completed')->count();

        return view('home', compact('packages', 'vendorCount', 'bookingCount', 'customerCount'));
    }
}
