<?php

namespace Modules\ERP\Models\Asset;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;

class AssetCategory extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_asset_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'depreciation_method',
        'depreciation_rate',
        'useful_life_years',
    ];

    protected $casts = [
        'depreciation_rate' => 'decimal:2',
        'useful_life_years' => 'integer',
    ];

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'asset_category_id');
    }
}
