<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorProfileController extends Controller
{
    public function edit()
    {
        $vendor = Vendor::where('user_id', Auth::id())->with('user')->firstOrFail();

        return view('vendor.profile.edit', compact('vendor'));
    }

    public function update(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();
        $user = Auth::user();

        // Validate vendor profile data
        $vendorData = $request->validate([
            'business_name' => 'required|string|max:255',
            'service_type' => 'required|string|max:100',
            'description' => 'required|string',
            'price_range' => 'required|string|max:100',
            'portfolio_images' => 'nullable|array',
            'portfolio_images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Validate user data
        $userData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        // Handle portfolio images upload
        if ($request->hasFile('portfolio_images')) {
            $images = [];
            foreach ($request->file('portfolio_images') as $image) {
                $path = $image->store('portfolio', 'public');
                $images[] = $path;
            }
            $vendorData['portfolio_images'] = array_merge($vendor->portfolio_images ?? [], $images);
        }

        // Update vendor profile
        $vendor->update($vendorData);

        // Update user data
        $user->update($userData);

        return redirect()->route('vendor.profile.edit')
            ->with('success', 'Profile updated successfully');
    }

    public function services()
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();
        $services = VendorService::where('vendor_id', $vendor->id)->get();

        return view('vendor.services.index', compact('services'));
    }

    public function storeService(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:100',
        ]);

        VendorService::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'duration' => $validated['duration'] ?? null,
        ]);

        return redirect()->route('vendor.services.index')
            ->with('success', 'Service added successfully');
    }

    public function deleteService($id)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstOrFail();
        $service = VendorService::where('vendor_id', $vendor->id)->findOrFail($id);
        $service->delete();

        return redirect()->route('vendor.services.index')
            ->with('success', 'Service deleted successfully');
    }
}
