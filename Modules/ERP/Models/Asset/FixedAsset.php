<?php

namespace Modules\ERP\Models\Asset;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\TenantAwareModel;

class FixedAsset extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_fixed_assets';

    protected $fillable = [
        'tenant_id',
        'asset_category_id',
        'name',
        'code',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'salvage_value',
        'location',
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'salvage_value' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assigned_to');
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(DepreciationSchedule::class, 'fixed_asset_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'fixed_asset_id');
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class, 'fixed_asset_id');
    }
}
