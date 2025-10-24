<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    use HasFactory;

    protected $table = 'event_schedule';

    protected $fillable = [
        'booking_id',
        'task_name',
        'description',
        'scheduled_date',
        'scheduled_time',
        'assigned_vendor_id',
        'status',
        'priority',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function assignedVendor()
    {
        return $this->belongsTo(Vendor::class, 'assigned_vendor_id');
    }
}
