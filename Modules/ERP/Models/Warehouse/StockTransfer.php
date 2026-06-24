<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class StockTransfer extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_stock_transfers';

    protected $fillable = [
        'tenant_id',
        'reference_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'transfer_date',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
