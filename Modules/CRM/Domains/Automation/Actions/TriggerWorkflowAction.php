<?php

namespace Modules\CRM\Domains\Automation\Actions;

use Modules\CRM\Domains\Automation\DTOs\WorkflowTriggerData;
use Illuminate\Support\Facades\Log;

class TriggerWorkflowAction
{
    public function execute(WorkflowTriggerData $data): void
    {
        // For now, we simply log the automation event.
        // In a full implementation, this would query the `crm_automations` table
        // to find active workflows matching $data->eventName and dispatch jobs.
        
        Log::info("Automation Engine: Received trigger [{$data->eventName}] for {$data->entityType}:{$data->entityId}", $data->payload);
        
        // Example: if ($data->eventName === 'lead.created') { dispatch(new RunWorkflowJob(...)); }
    }
}
