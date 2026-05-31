<?php

namespace Modules\CRM\Domains\Leads\Actions;

use Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;

class DeleteLeadAction
{
    public function execute(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            // Note: The Lead model's booted method already handles deleting related SequenceStates on soft delete.
            $lead->delete();
        });
    }
}
