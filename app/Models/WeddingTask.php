<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeddingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'task_title',
        'description',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
