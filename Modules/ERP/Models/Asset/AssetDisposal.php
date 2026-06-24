<?php

namespace Modules\ERP\Models\Asset;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class AssetDisposal extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_asset_disposals';

    protected $fillable = [
        'tenant_id',
        'fixed_asset_id',
        'disposal_date',
        'disposal_type',
        'disposal_value',
        'notes',
        'journal_entry_id',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'disposal_value' => 'decimal:2',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
