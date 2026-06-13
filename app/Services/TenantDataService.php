<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cross-module data export/import service for platform users.
 *
 * This service aggregates data from all modules a user has access to.
 * ERP, CRM, and other modules are accessed via class_exists() guards
 * to maintain module-level isolation — no hard imports from module namespaces.
 */
class TenantDataService
{
    /**
     * Export all data belonging to a tenant as a structured array.
     */
    public function exportData(int $userId): array
    {
        $export = [
            'metadata' => [
                'exported_at' => Carbon::now()->toIso8601String(),
                'user_id'     => $userId,
                'version'     => '1.0',
            ],
            'crm' => [],
            'erp' => [],
        ];

        // CRM module export (optional)
        if (class_exists(\Modules\CRM\Models\Campaign::class)) {
            $export['crm']['campaigns'] = \Modules\CRM\Models\Campaign::where('user_id', $userId)->get()->toArray();
        }
        if (class_exists(\Modules\CRM\Models\Lead::class)) {
            $export['crm']['leads'] = \Modules\CRM\Models\Lead::where('user_id', $userId)->get()->toArray();
        }

        // ERP module export (optional — uses ERP Tenant to scope export correctly)
        if (class_exists(\Modules\ERP\Models\Tenant::class)) {
            $tenant = \Modules\ERP\Models\Tenant::where('user_id', $userId)->first();
            if ($tenant) {
                if (class_exists(\Modules\ERP\Models\TenantClient::class)) {
                    $export['erp']['clients'] = \Modules\ERP\Models\TenantClient::where('tenant_id', $tenant->id)->get()->toArray();
                }
                if (class_exists(\Modules\ERP\Models\Invoice::class)) {
                    $export['erp']['invoices'] = \Modules\ERP\Models\Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->toArray();
                }
                if (class_exists(\Modules\ERP\Models\ERPTask::class)) {
                    $export['erp']['tasks'] = \Modules\ERP\Models\ERPTask::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->toArray();
                }
            }
        }

        return $export;
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
            // Wipe and restore CRM data (optional)
            if (class_exists(\Modules\CRM\Models\Lead::class)) {
                \Modules\CRM\Models\Lead::where('user_id', $userId)->forceDelete();
            }
            if (class_exists(\Modules\CRM\Models\Campaign::class)) {
                \Modules\CRM\Models\Campaign::where('user_id', $userId)->forceDelete();
            }

            // Wipe and restore ERP data (optional)
            if (class_exists(\Modules\ERP\Models\Tenant::class)) {
                $tenant = \Modules\ERP\Models\Tenant::where('user_id', $userId)->first();
                if ($tenant) {
                    if (class_exists(\Modules\ERP\Models\ERPTask::class)) {
                        \Modules\ERP\Models\ERPTask::where('tenant_id', $tenant->id)->forceDelete();
                    }
                    if (class_exists(\Modules\ERP\Models\Invoice::class)) {
                        \Modules\ERP\Models\Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->forceDelete();
                    }
                    if (class_exists(\Modules\ERP\Models\TenantClient::class)) {
                        \Modules\ERP\Models\TenantClient::where('tenant_id', $tenant->id)->forceDelete();
                    }
                }
            }

            // Restore CRM campaigns
            if (!empty($data['crm']['campaigns']) && class_exists(\Modules\CRM\Models\Campaign::class)) {
                foreach ($data['crm']['campaigns'] as $campaign) {
                    \Modules\CRM\Models\Campaign::insert($campaign);
                }
            }

            // Restore CRM leads
            if (!empty($data['crm']['leads']) && class_exists(\Modules\CRM\Models\Lead::class)) {
                foreach ($data['crm']['leads'] as $lead) {
                    \Modules\CRM\Models\Lead::insert($lead);
                }
            }

            // Restore ERP clients
            if (!empty($data['erp']['clients']) && class_exists(\Modules\ERP\Models\TenantClient::class)) {
                foreach ($data['erp']['clients'] as $client) {
                    \Modules\ERP\Models\TenantClient::insert($client);
                }
            }

            // Restore ERP invoices
            if (!empty($data['erp']['invoices']) && class_exists(\Modules\ERP\Models\Invoice::class)) {
                foreach ($data['erp']['invoices'] as $invoice) {
                    \Modules\ERP\Models\Invoice::insert($invoice);
                }
            }

            // Restore ERP tasks
            if (!empty($data['erp']['tasks']) && class_exists(\Modules\ERP\Models\ERPTask::class)) {
                foreach ($data['erp']['tasks'] as $task) {
                    \Modules\ERP\Models\ERPTask::insert($task);
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
