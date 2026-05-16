<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'referrer_id', 'referee_id', 'level', 'status'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referee_id');
    }
}
