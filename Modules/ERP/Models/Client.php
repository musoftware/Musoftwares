<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends TenantModel
{
    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'address', 'referral_code', 'referred_by'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Client::class, 'referred_by');
    }
}
