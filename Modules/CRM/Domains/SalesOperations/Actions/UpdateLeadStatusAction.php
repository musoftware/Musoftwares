<?php

namespace Modules\CRM\Domains\SalesOperations\Actions;

use Modules\CRM\Domains\SalesOperations\DTOs\LeadStatusUpdateData;
use Illuminate\Support\Facades\DB;

class UpdateLeadStatusAction
{
    public function execute(LeadStatusUpdateData $data): void
    {
        DB::transaction(function () use ($data) {
            $updateData = [
                'pipeline_stage' => $data->newStatus,
                'last_contacted_at' => now(),
                'updated_at' => now(),
            ];

            // If the status is WON or LOST, we clear the SLA breach counter to stop alerts
            if (in_array($data->newStatus, ['WON', 'LOST'])) {
                $updateData['sla_breach_at'] = null;
            }

            DB::table('leads')->where('id', $data->leadId)->update($updateData);

            if ($data->notes) {
                DB::table('crm_activities')->insert([
                    'lead_id' => $data->leadId,
                    'user_id' => $data->actionById,
                    'type' => 'status_change',
                    'description' => "Status changed to {$data->newStatus}. Notes: {$data->notes}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
