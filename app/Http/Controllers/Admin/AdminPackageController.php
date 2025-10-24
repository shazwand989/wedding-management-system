<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeddingPackage;
use Illuminate\Http\Request;

class AdminPackageController extends Controller
{
    public function index()
    {
        $packages = WeddingPackage::orderBy('price', 'asc')->paginate(10);

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'max_guests' => 'required|integer|min:1',
            'features' => 'required|array',
            'features.*' => 'string',
            'status' => 'required|in:active,inactive',
        ]);

        WeddingPackage::create($validated);

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package created successfully');
    }

    public function show($id)
    {
        $package = WeddingPackage::findOrFail($id);

        return view('admin.packages.show', compact('package'));
    }

    public function edit($id)
    {
        $package = WeddingPackage::findOrFail($id);

        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, $id)
    {
        $package = WeddingPackage::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'max_guests' => 'required|integer|min:1',
            'features' => 'required|array',
            'features.*' => 'string',
            'status' => 'required|in:active,inactive',
        ]);

        $package->update($validated);

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package updated successfully');
    }

    public function destroy($id)
    {
        $package = WeddingPackage::findOrFail($id);
        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package deleted successfully');
    }
}
