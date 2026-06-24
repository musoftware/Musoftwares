<?php

namespace Modules\ERP\Models\Tax;

use Modules\ERP\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxRule extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_tax_rules';

    protected $fillable = [
        'tenant_id', 'name', 'tax_rate_id', 'tax_group_id', 'condition', 'is_active'
    ];

    protected $casts = [
        'condition' => 'array',
        'is_active' => 'boolean',
    ];
}
