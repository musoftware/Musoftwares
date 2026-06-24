<?php

namespace Modules\ERP\Models\Tax;

use Modules\ERP\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxRate extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_tax_rates';

    protected $fillable = [
        'tenant_id', 'name', 'rate', 'type', 'is_active', 'description'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
