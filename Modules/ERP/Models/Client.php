<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends TenantModel
{
    protected $table = 'tenant_clients';

    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'address', 'currency', 'referral_code', 'referred_by'];

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

    public function notes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Core\Models\AdminNote::class, 'noteable');
    }
}
