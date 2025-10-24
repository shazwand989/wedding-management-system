<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'customer');

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('bookings')->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show($id)
    {
        $customer = User::where('role', 'customer')
            ->with(['bookings.package'])
            ->findOrFail($id);

        return view('admin.customers.show', compact('customer'));
    }
}
