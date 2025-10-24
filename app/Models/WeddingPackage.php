<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeddingPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_hours',
        'max_guests',
        'features',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
    ];

    // Relationships
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'package_id');
    }
}
