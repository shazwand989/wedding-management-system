<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'category',
        'description',
        'amount',
        'vendor_name',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
