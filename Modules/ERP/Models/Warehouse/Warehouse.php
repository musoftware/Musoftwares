<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;

class Warehouse extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_warehouses';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class);
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function inventoryCounts(): HasMany
    {
        return $this->hasMany(InventoryCount::class);
    }
}
