<?php

namespace Modules\CRM\Domains\Leads\Actions;

use Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;

class UpdateLeadStatusAction
{
    public function execute(Lead $lead, string $status): Lead
    {
        return DB::transaction(function () use ($lead, $status) {
            $lead->status = $status;
            $lead->save();

            // Here we might trigger events, like LeadStatusChanged, 
            // which can be picked up by the automation engine.
            
            return $lead;
        });
    }
}
