<?php

namespace Modules\ERP\Models\Asset;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\TenantAwareModel;

class AssetTransfer extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_asset_transfers';

    protected $fillable = [
        'tenant_id',
        'fixed_asset_id',
        'from_location',
        'to_location',
        'from_employee_id',
        'to_employee_id',
        'transfer_date',
        'reason',
        'status',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'from_employee_id');
    }

    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'to_employee_id');
    }
}
