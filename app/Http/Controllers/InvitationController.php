<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function show($code)
    {
        $event = Event::where('invitation_code', $code)
            ->where('status', 'published')
            ->firstOrFail();

        // Track invitation view
        if (request()->has('email')) {
            $guest = EventGuest::where('event_id', $event->id)
                ->where('email', request('email'))
                ->first();

            if ($guest && !$guest->viewed_invitation) {
                $guest->update([
                    'viewed_invitation' => true,
                    'viewed_at' => now(),
                ]);
            }
        }

        return view('invitation.show', compact('event'));
    }
}
