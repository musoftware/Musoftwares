<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class BankAccount extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_bank_accounts';
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
