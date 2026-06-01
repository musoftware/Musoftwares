<?php

namespace Modules\ERP\Models;

class TenantStorageProvider extends TenantModel
{
    protected $table = 'erp_tenant_storage_providers';

    protected $fillable = [
        'tenant_id',
        'name',
        'driver',
        'key',
        'secret',
        'region',
        'bucket',
        'endpoint',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'secret', // Never expose secret keys to the frontend
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
