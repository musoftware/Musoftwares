<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\Product;

class PurchaseOrderItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'business_unit_price',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'business_total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'business_unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'business_total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
