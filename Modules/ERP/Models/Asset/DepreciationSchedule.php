<?php

namespace Modules\ERP\Models\Asset;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class DepreciationSchedule extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_depreciation_schedules';

    protected $fillable = [
        'tenant_id',
        'fixed_asset_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'is_posted',
        'journal_entry_id',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
