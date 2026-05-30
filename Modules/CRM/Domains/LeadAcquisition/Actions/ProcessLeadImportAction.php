<?php

namespace Modules\CRM\Domains\LeadAcquisition\Actions;

use Modules\CRM\Domains\LeadAcquisition\DTOs\LeadImportData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLeadImportAction
{
    /**
     * Parse array data and insert new leads, preventing duplicates by phone.
     * Returns the number of successfully imported leads.
     */
    public function execute(LeadImportData $data): int
    {
        $importedCount = 0;
        $tenantId = $data->tenantId;

        $insertData = [];

        foreach ($data->data as $row) {
            $phone = $row['phone'] ?? null;
            if (!$phone) {
                continue; // Skip leads without phone
            }

            $insertData[] = [
                'tenant_id' => $tenantId,
                'name' => $row['name'] ?? 'Unknown Lead',
                'phone' => $phone,
                'email' => $row['email'] ?? null,
                'pipeline_stage' => 'NEW',
                'source' => $row['source'] ?? 'CSV Import',
                'assigned_to_id' => $data->assignedToId,
                'branch_id' => $data->branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Hardware-level idempotency protection.
        // Uses the unique (tenant_id, phone) constraint to automatically drop race-condition duplicates.
        if (!empty($insertData)) {
            $importedCount = DB::table('leads')->insertOrIgnore($insertData);
        }

        Log::info("Attempted to import " . count($insertData) . " leads. Successfully inserted {$importedCount} new leads into the system.");
        return $importedCount;
    }
}
