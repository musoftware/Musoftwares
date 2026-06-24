<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;

class WarehouseZone extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_warehouse_zones';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class);
    }
}
