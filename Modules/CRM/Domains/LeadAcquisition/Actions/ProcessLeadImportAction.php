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
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        foreach ($data->data as $row) {
            // Check for duplicate by phone
            $phone = $row['phone'] ?? null;
            if (!$phone) {
                continue; // Skip leads without phone
            }

            $exists = DB::table('leads')
                ->where('tenant_id', $tenantId)
                ->where('phone', $phone)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('leads')->insert([
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
            ]);

            $importedCount++;
        }

        Log::info("Imported {$importedCount} new leads into the system.");
        return $importedCount;
    }
}
