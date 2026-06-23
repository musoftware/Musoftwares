<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Currency;

class Product extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_products';

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
        'cost_price',
        'currency_id',
        'category_id',
        'barcode',
        'uom',
        'image_path',
        'tax_rate',
        'stock_quantity',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stockLogs(): HasMany
    {
        return $this->hasMany(ProductStockLog::class);
    }

    public function checkLowStock(float $oldQuantity): void
    {
        if ($this->reorder_level !== null && $this->stock_quantity <= $this->reorder_level && $oldQuantity > $this->reorder_level) {
            $tenantUser = \App\Models\User::find($this->tenant->user_id ?? null);
            if ($tenantUser) {
                $tenantUser->notify(new \Modules\ERP\Notifications\LowStockNotification($this));
            }
        }
    }
}
