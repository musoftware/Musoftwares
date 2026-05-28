<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProductStockLog extends TenantModel
{
    protected $table = 'erp_product_stock_logs';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'user_id',
        'change_amount',
        'new_quantity',
        'reason',
    ];

    protected $casts = [
        'change_amount' => 'decimal:2',
        'new_quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
