<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::where('host_id', Auth::id())
            ->withCount(['rsvps', 'guests'])
            ->orderBy('event_date', 'desc')
            ->paginate(10);

        return view('customer.events.index', compact('events'));
    }

    public function create()
    {
        $bookings = Booking::where('customer_id', Auth::id())
            ->where('booking_status', '!=', 'cancelled')
            ->get();

        return view('customer.events.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string',
            'booking_id' => 'nullable|exists:bookings,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string',
            'venue_google_maps' => 'nullable|url',
            'max_guests' => 'nullable|integer|min:1',
            'special_instructions' => 'nullable|string',
            'rsvp_deadline' => 'nullable|date',
            'allow_plus_one' => 'boolean',
        ]);

        $validated['host_id'] = Auth::id();
        $validated['invitation_code'] = Str::random(10);
        $validated['status'] = 'draft';

        $event = Event::create($validated);

        return redirect()->route('customer.events.show', $event->id)
            ->with('success', 'Event created successfully!');
    }

    public function show($id)
    {
        $event = Event::where('host_id', Auth::id())
            ->with(['rsvps', 'guests'])
            ->findOrFail($id);

        $stats = $event->rsvp_stats;

        return view('customer.events.show', compact('event', 'stats'));
    }

    public function edit($id)
    {
        $event = Event::where('host_id', Auth::id())->findOrFail($id);
        $bookings = Booking::where('customer_id', Auth::id())
            ->where('booking_status', '!=', 'cancelled')
            ->get();

        return view('customer.events.edit', compact('event', 'bookings'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::where('host_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string',
            'booking_id' => 'nullable|exists:bookings,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string',
            'venue_google_maps' => 'nullable|url',
            'max_guests' => 'nullable|integer|min:1',
            'special_instructions' => 'nullable|string',
            'rsvp_deadline' => 'nullable|date',
            'allow_plus_one' => 'boolean',
            'status' => 'in:draft,published,closed',
        ]);

        $event->update($validated);

        return redirect()->route('customer.events.show', $event->id)
            ->with('success', 'Event updated successfully!');
    }

    public function destroy($id)
    {
        $event = Event::where('host_id', Auth::id())->findOrFail($id);
        $event->delete();

        return redirect()->route('customer.events.index')
            ->with('success', 'Event deleted successfully');
    }

    public function guests($id)
    {
        $event = Event::where('host_id', Auth::id())
            ->with(['guests.rsvp'])
            ->findOrFail($id);

        return view('customer.events.guests', compact('event'));
    }

    public function addGuest(Request $request, $id)
    {
        $event = Event::where('host_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'group_name' => 'nullable|string',
        ]);

        $validated['event_id'] = $event->id;

        EventGuest::create($validated);

        return back()->with('success', 'Guest added successfully!');
    }

    public function sendInvitations($id)
    {
        $event = Event::where('host_id', Auth::id())->findOrFail($id);

        // Update event status
        $event->update(['status' => 'published']);

        // Send invitations to all guests
        foreach ($event->guests as $guest) {
            // Send email/SMS notification
            // This would integrate with your email/SMS service
            $guest->update([
                'invitation_sent' => true,
                'invitation_sent_at' => now(),
            ]);
        }

        return back()->with('success', 'Invitations sent to all guests!');
    }
}
