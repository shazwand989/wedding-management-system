<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToyyibpayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_code',
        'booking_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
