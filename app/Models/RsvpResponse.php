<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RsvpResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'guest_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'response_status',
        'number_of_guests',
        'message',
        'dietary_restrictions',
        'custom_responses',
        'checked_in',
        'checked_in_at',
        'qr_code',
    ];

    protected $casts = [
        'custom_responses' => 'array',
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rsvp) {
            if (empty($rsvp->qr_code)) {
                $rsvp->qr_code = \Illuminate\Support\Str::random(20);
            }
        });
    }

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function guest()
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    // Generate QR Code
    public function generateQrCode()
    {
        return QrCode::size(200)->generate($this->qr_code);
    }
}
