<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends TenantModel
{
    protected $table = 'erp_product_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
