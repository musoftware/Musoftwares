<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;
use Modules\ERP\Models\TeamMember;

class PurchaseRequest extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_purchase_requests';

    protected $fillable = [
        'tenant_id',
        'request_number',
        'requester_id',
        'status',
        'notes',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'requester_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
