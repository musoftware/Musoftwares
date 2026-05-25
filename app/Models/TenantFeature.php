<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFeature extends Model
{
    protected $table = 'tenant_features';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if a feature is currently active (not expired).
     */
    public function isActive(): bool
    {
        if (!$this->expires_at) {
            return true; // No expiration = lifetime access
        }

        return $this->expires_at->isFuture();
    }
}
