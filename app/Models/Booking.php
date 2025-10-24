<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'package_id',
        'event_date',
        'event_time',
        'venue_name',
        'venue_address',
        'guest_count',
        'special_requests',
        'total_amount',
        'paid_amount',
        'payment_status',
        'booking_status',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function package()
    {
        return $this->belongsTo(WeddingPackage::class, 'package_id');
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'booking_vendors')
                    ->withPivot('service_type', 'agreed_price', 'status', 'notes')
                    ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function schedule()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function toyyibpayTransactions()
    {
        return $this->hasMany(ToyyibpayTransaction::class);
    }

    // Helper methods
    public function getRemainingAmount()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function isFullyPaid()
    {
        return $this->paid_amount >= $this->total_amount;
    }
}
