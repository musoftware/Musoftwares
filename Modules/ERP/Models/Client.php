<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends TenantAwareModel
{
    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'address'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
