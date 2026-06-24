<?php

namespace Modules\ERP\Models\Tax;

use Modules\ERP\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaxReporting extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_tax_reportings';

    protected $fillable = [
        'tenant_id', 'report_name', 'period_start', 'period_end', 'total_tax_collected', 'total_tax_paid', 'status', 'generated_at'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_tax_collected' => 'decimal:4',
        'total_tax_paid' => 'decimal:4',
        'generated_at' => 'datetime',
    ];
}
