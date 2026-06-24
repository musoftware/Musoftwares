<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class GoodsReceiptNote extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_goods_receipt_notes';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'grn_number',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
