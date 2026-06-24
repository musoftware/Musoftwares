<?php

namespace Modules\ERP\Models\Tax;

use Modules\ERP\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxGroup extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_tax_groups';

    protected $fillable = [
        'tenant_id', 'name', 'is_active', 'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
