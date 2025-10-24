<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'page_url',
        'page_title',
        'referrer',
        'device_type',
        'browser',
        'os',
        'country',
    ];
}
