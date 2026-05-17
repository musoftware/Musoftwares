<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantClient extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'address', 'currency', 'country_code', 'referral_code', 'referred_by'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referred_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function wallet()
    {
        return $this->hasOne(ClientWallet::class, 'client_id');
    }
}
