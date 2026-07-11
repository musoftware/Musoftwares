<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'ip_address',
        'user_agent',
        'agreed_at',
        'policy_snapshot',
        'is_latest_version',
        'version',
    ];

    public $timestamps = true;
}
