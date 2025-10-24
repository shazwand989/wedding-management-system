<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'full_name',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Relationships
    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function weddingTasks()
    {
        return $this->hasMany(WeddingTask::class, 'customer_id');
    }

    public function weddingBudget()
    {
        return $this->hasOne(WeddingBudget::class, 'customer_id');
    }

    public function budgetExpenses()
    {
        return $this->hasMany(BudgetExpense::class, 'customer_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    public function isVendor()
    {
        return $this->role === 'vendor';
    }
}
