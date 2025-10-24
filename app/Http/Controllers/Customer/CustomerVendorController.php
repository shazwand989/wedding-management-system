<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class CustomerVendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::where('status', 'active')->with('user');

        // Filter by service type
        if ($request->has('service_type') && $request->service_type != '') {
            $query->where('service_type', $request->service_type);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort by rating
        if ($request->has('sort') && $request->sort == 'rating') {
            $query->orderBy('rating', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $vendors = $query->paginate(12);

        // Get service types for filter
        $serviceTypes = Vendor::where('status', 'active')
            ->distinct()
            ->pluck('service_type');

        return view('customer.vendors.index', compact('vendors', 'serviceTypes'));
    }

    public function show($id)
    {
        $vendor = Vendor::where('status', 'active')
            ->with(['user', 'reviews.customer'])
            ->findOrFail($id);

        return view('customer.vendors.show', compact('vendor'));
    }
}
