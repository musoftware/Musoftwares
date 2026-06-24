<?php

namespace Modules\ERP\Models\Tax;

use Modules\ERP\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxCalculator extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_tax_calculators';

    protected $fillable = [
        'tenant_id', 'name', 'configuration', 'is_default'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_default' => 'boolean',
    ];
}
