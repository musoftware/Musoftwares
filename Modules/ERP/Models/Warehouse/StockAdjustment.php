<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class StockAdjustment extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_stock_adjustments';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'reference_number',
        'adjustment_date',
        'reason',
        'status',
        'notes',
    ];

    protected $casts = [
        'adjustment_date' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
