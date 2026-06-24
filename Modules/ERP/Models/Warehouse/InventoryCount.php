<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class InventoryCount extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_inventory_counts';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'reference_number',
        'count_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
