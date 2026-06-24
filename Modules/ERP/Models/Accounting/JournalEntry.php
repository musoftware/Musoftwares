<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class JournalEntry extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_journal_entries';
    protected $guarded = [];

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function ledger()
    {
        return $this->belongsTo(GeneralLedger::class, 'ledger_id');
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function document()
    {
        return $this->morphTo();
    }
}
