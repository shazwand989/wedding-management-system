<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'invitation_sent',
        'invitation_sent_at',
        'viewed_invitation',
        'viewed_at',
        'group_name',
    ];

    protected $casts = [
        'invitation_sent' => 'boolean',
        'invitation_sent_at' => 'datetime',
        'viewed_invitation' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rsvp()
    {
        return $this->hasOne(RsvpResponse::class, 'guest_email', 'email')
                    ->where('event_id', $this->event_id);
    }
}
