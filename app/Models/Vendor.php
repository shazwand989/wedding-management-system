<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'service_type',
        'description',
        'price_range',
        'location',
        'specialties',
        'portfolio_images',
        'rating',
        'total_reviews',
        'status',
    ];

    protected $casts = [
        'portfolio_images' => 'array',
        'rating' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_vendors')
                    ->withPivot('service_type', 'agreed_price', 'status', 'notes')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
