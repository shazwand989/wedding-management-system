<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeddingBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_budget',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
