<?php

namespace Modules\ERP\Models\Accounting;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\TenantAwareModel;

class LedgerAccount extends TenantAwareModel
{
    use SoftDeletes;

    protected $guarded = [];

    public function entries()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
