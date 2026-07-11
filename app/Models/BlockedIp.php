<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockedIp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];
}
