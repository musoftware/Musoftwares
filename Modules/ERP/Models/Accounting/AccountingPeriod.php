<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class AccountingPeriod extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_accounting_periods';
    protected $guarded = [];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
