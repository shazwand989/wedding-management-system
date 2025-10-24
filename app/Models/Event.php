<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'host_id',
        'booking_id',
        'title',
        'description',
        'event_type',
        'event_date',
        'event_time',
        'venue_name',
        'venue_address',
        'venue_google_maps',
        'invitation_code',
        'max_guests',
        'custom_fields',
        'special_instructions',
        'cover_image',
        'status',
        'rsvp_enabled',
        'rsvp_deadline',
        'allow_plus_one',
        'send_reminders',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'event_time' => 'datetime',
        'rsvp_deadline' => 'datetime',
        'custom_fields' => 'array',
        'rsvp_enabled' => 'boolean',
        'allow_plus_one' => 'boolean',
        'send_reminders' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->invitation_code)) {
                $event->invitation_code = Str::random(10);
            }
        });
    }

    // Relationships
    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function rsvps()
    {
        return $this->hasMany(RsvpResponse::class);
    }

    public function guests()
    {
        return $this->hasMany(EventGuest::class);
    }

    // Helper methods
    public function getInvitationUrlAttribute()
    {
        return route('invitation.show', $this->invitation_code);
    }

    public function getRsvpStatsAttribute()
    {
        return [
            'total' => $this->rsvps->count(),
            'yes' => $this->rsvps->where('response_status', 'yes')->count(),
            'no' => $this->rsvps->where('response_status', 'no')->count(),
            'maybe' => $this->rsvps->where('response_status', 'maybe')->count(),
            'pending' => $this->guests->count() - $this->rsvps->count(),
        ];
    }

    public function getTotalConfirmedGuestsAttribute()
    {
        return $this->rsvps->where('response_status', 'yes')->sum('number_of_guests');
    }
}
