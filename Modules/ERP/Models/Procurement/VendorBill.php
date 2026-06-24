<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;
use App\Models\Currency;

class VendorBill extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_vendor_bills';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_order_id',
        'bill_number',
        'status',
        'currency_id',
        'exchange_rate',
        'tax_amount',
        'total_amount',
        'business_total_amount',
        'issue_date',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'business_total_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
