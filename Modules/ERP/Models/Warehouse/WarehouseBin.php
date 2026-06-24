<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class WarehouseBin extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_warehouse_bins';

    protected $fillable = [
        'tenant_id',
        'warehouse_zone_id',
        'name',
        'code',
        'barcode',
        'max_weight',
        'max_volume',
        'is_active',
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id');
    }
}
