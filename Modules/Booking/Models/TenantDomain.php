<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TenantDomain extends Model
{
    protected $table = 'tenant_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_verified',
        'ssl_status',
        'verification_token',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
