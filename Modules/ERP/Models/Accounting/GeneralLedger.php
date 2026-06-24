<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class GeneralLedger extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_general_ledgers';
    protected $guarded = [];

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'ledger_id');
    }
}
