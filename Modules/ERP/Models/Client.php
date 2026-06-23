<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_tenant_clients';

    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'address', 'currency'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\AdminNote::class, 'noteable');
    }
}

