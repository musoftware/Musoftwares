<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;
use App\Models\Currency;

class PurchaseOrder extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_purchase_orders';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_request_id',
        'po_number',
        'status',
        'currency_id',
        'exchange_rate',
        'tax_amount',
        'total_amount',
        'business_total_amount',
        'expected_delivery_date',
        'notes',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'business_total_amount' => 'decimal:2',
        'expected_delivery_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class);
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }
}
