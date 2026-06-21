<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class RateLimit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'module',
        'tenant_id',
        'ip_address',
        'max_requests',
        'decay_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
