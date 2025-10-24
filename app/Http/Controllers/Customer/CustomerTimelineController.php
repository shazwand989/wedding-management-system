<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTimelineController extends Controller
{
    public function index()
    {
        $timelines = Timeline::where('customer_id', Auth::id())
            ->orderBy('task_date', 'asc')
            ->get();

        return view('customer.timeline.index', compact('timelines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'task_date' => 'required|date',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        Timeline::create([
            'customer_id' => Auth::id(),
            'task_name' => $validated['task_name'],
            'task_date' => $validated['task_date'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('customer.timeline.index')
            ->with('success', 'Timeline task added successfully');
    }

    public function update(Request $request, $id)
    {
        $timeline = Timeline::where('customer_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'task_date' => 'required|date',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $timeline->update($validated);

        return redirect()->route('customer.timeline.index')
            ->with('success', 'Timeline task updated successfully');
    }

    public function destroy($id)
    {
        $timeline = Timeline::where('customer_id', Auth::id())->findOrFail($id);
        $timeline->delete();

        return redirect()->route('customer.timeline.index')
            ->with('success', 'Timeline task deleted successfully');
    }
}
