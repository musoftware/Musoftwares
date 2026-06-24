<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class AccountingRule extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_accounting_rules';
    protected $guarded = [];

    public function debitAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_id');
    }
}
