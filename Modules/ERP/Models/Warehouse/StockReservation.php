<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\ERP\Models\Product;
use Modules\ERP\Models\TenantAwareModel;

class StockReservation extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_stock_reservations';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'source_type',
        'source_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
