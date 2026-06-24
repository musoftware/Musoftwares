<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class FiscalYear extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_fiscal_years';
    protected $guarded = [];

    public function periods()
    {
        return $this->hasMany(AccountingPeriod::class);
    }
}
