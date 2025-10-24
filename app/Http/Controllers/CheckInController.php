<?php

namespace App\Http\Controllers;

use App\Models\RsvpResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function scanner($eventId)
    {
        return view('checkin.scanner', compact('eventId'));
    }

    public function scan(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
        ]);

        $rsvp = RsvpResponse::where('qr_code', $validated['qr_code'])->first();

        if (!$rsvp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code',
            ], 404);
        }

        if ($rsvp->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Guest already checked in at ' . $rsvp->checked_in_at->format('g:i A'),
                'rsvp' => $rsvp,
            ]);
        }

        $rsvp->update([
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guest checked in successfully!',
            'rsvp' => $rsvp,
        ]);
    }
}
