<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Lead;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\ERPTask;
use Carbon\Carbon;

class TenantDataService
{
    /**
     * Export all data belonging to a tenant as a structured array.
     */
    public function exportData(int $userId): array
    {
        return [
            'metadata' => [
                'exported_at' => Carbon::now()->toIso8601String(),
                'user_id' => $userId,
                'version' => '1.0',
            ],
            'crm' => [
                'campaigns' => Campaign::where('user_id', $userId)->get()->toArray(),
                'leads' => Lead::where('user_id', $userId)->get()->toArray(),
            ],
            'erp' => [
                'clients' => Client::where('user_id', $userId)->get()->toArray(),
                'invoices' => Invoice::where('user_id', $userId)->get()->toArray(),
                'tasks' => ERPTask::where('user_id', $userId)->get()->toArray(),
            ]
        ];
    }

    /**
     * Import structured data for a tenant. 
     * Because of relational IDs, we wipe existing data and re-insert to prevent duplicates.
     */
    public function importData(int $userId, array $data): bool
    {
        if (!isset($data['metadata']['user_id']) || $data['metadata']['user_id'] != $userId) {
            throw new \Exception("Backup file does not belong to your account.");
        }

        DB::beginTransaction();
        try {
            // 1. Wipe existing data for this tenant
            Lead::where('user_id', $userId)->forceDelete();
            Campaign::where('user_id', $userId)->forceDelete();
            
            ERPTask::where('user_id', $userId)->forceDelete();
            Invoice::where('user_id', $userId)->forceDelete();
            Client::where('user_id', $userId)->forceDelete();

            // 2. Import CRM Data
            if (isset($data['crm']['campaigns'])) {
                foreach ($data['crm']['campaigns'] as $campaign) {
                    Campaign::insert($campaign);
                }
            }
            if (isset($data['crm']['leads'])) {
                foreach ($data['crm']['leads'] as $lead) {
                    Lead::insert($lead);
                }
            }

            // 3. Import ERP Data
            if (isset($data['erp']['clients'])) {
                foreach ($data['erp']['clients'] as $client) {
                    Client::insert($client);
                }
            }
            if (isset($data['erp']['invoices'])) {
                foreach ($data['erp']['invoices'] as $invoice) {
                    Invoice::insert($invoice);
                }
            }
            if (isset($data['erp']['tasks'])) {
                foreach ($data['erp']['tasks'] as $task) {
                    ERPTask::insert($task);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tenant Import Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
