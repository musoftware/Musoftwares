<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class BankReconciliation extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_bank_reconciliations';
    protected $guarded = [];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
