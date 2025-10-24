<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\RsvpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RsvpController extends Controller
{
    public function store(Request $request, $code)
    {
        $event = Event::where('invitation_code', $code)
            ->where('status', 'published')
            ->firstOrFail();

        // Check RSVP deadline
        if ($event->rsvp_deadline && now()->isAfter($event->rsvp_deadline)) {
            return back()->with('error', 'RSVP deadline has passed.');
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email',
            'guest_phone' => 'nullable|string',
            'response_status' => 'required|in:yes,no,maybe',
            'number_of_guests' => 'required_if:response_status,yes|integer|min:1',
            'message' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string',
        ]);

        // Check if already RSVP'd
        $existingRsvp = RsvpResponse::where('event_id', $event->id)
            ->where('guest_email', $validated['guest_email'])
            ->first();

        if ($existingRsvp) {
            // Update existing RSVP
            $existingRsvp->update($validated);
            $rsvp = $existingRsvp;
        } else {
            // Create new RSVP
            $validated['event_id'] = $event->id;
            $validated['qr_code'] = Str::random(20);
            $rsvp = RsvpResponse::create($validated);
        }

        return redirect()->route('rsvp.success', $rsvp->id)
            ->with('success', 'Your RSVP has been recorded!');
    }

    public function success($id)
    {
        $rsvp = RsvpResponse::with('event')->findOrFail($id);

        return view('rsvp.success', compact('rsvp'));
    }
}
