<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Lead;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;

/**
 * Cross-module data export/import service for platform users.
 *
 * This service aggregates data from all modules a user has access to.
 * ERP, CRM, and other modules are accessed via class_exists() guards
 * to maintain module-level isolation — no hard imports from module namespaces.
 */
class TenantDataService extends BaseService
{
    /**
     * Export all data belonging to a tenant as a structured array.
     */
    public function exportData(int $userId): array
    {
        $export = [
            'metadata' => [
                'exported_at' => Carbon::now()->toIso8601String(),
                'user_id' => $userId,
                'version' => '1.0',
            ],
            'crm' => [],
            'erp' => [],
        ];

        // CRM module export (optional)
        if (class_exists(Campaign::class)) {
            $export['crm']['campaigns'] = Campaign::where('user_id', $userId)->get()->toArray();
        }
        if (class_exists(Lead::class)) {
            $export['crm']['leads'] = Lead::where('user_id', $userId)->get()->toArray();
        }

        // ERP module export (optional — uses ERP Tenant to scope export correctly)
        if (class_exists(Tenant::class)) {
            $tenant = Tenant::where('user_id', $userId)->first();
            if ($tenant) {
                if (class_exists(TenantClient::class)) {
                    $export['erp']['clients'] = TenantClient::where('tenant_id', $tenant->id)->get()->toArray();
                }
                if (class_exists(Invoice::class)) {
                    $export['erp']['invoices'] = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->toArray();
                }
                if (class_exists(ERPTask::class)) {
                    $export['erp']['tasks'] = ERPTask::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->toArray();
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
        if (! isset($data['metadata']['user_id']) || $data['metadata']['user_id'] != $userId) {
            throw new \Exception('Backup file does not belong to your account.');
        }

        DB::beginTransaction();
        try {
            // Wipe and restore CRM data (optional)
            if (class_exists(Lead::class)) {
                Lead::where('user_id', $userId)->forceDelete();
            }
            if (class_exists(Campaign::class)) {
                Campaign::where('user_id', $userId)->forceDelete();
            }

            // Wipe and restore ERP data (optional)
            if (class_exists(Tenant::class)) {
                $tenant = Tenant::where('user_id', $userId)->first();
                if ($tenant) {
                    if (class_exists(ERPTask::class)) {
                        ERPTask::where('tenant_id', $tenant->id)->forceDelete();
                    }
                    if (class_exists(Invoice::class)) {
                        Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->forceDelete();
                    }
                    if (class_exists(TenantClient::class)) {
                        TenantClient::where('tenant_id', $tenant->id)->forceDelete();
                    }
                }
            }

            // Restore CRM campaigns
            if (! empty($data['crm']['campaigns']) && class_exists(Campaign::class)) {
                foreach ($data['crm']['campaigns'] as $campaign) {
                    Campaign::insert($campaign);
                }
            }

            // Restore CRM leads
            if (! empty($data['crm']['leads']) && class_exists(Lead::class)) {
                foreach ($data['crm']['leads'] as $lead) {
                    Lead::insert($lead);
                }
            }

            // Restore ERP clients
            if (! empty($data['erp']['clients']) && class_exists(TenantClient::class)) {
                foreach ($data['erp']['clients'] as $client) {
                    TenantClient::insert($client);
                }
            }

            // Restore ERP invoices
            if (! empty($data['erp']['invoices']) && class_exists(Invoice::class)) {
                foreach ($data['erp']['invoices'] as $invoice) {
                    Invoice::insert($invoice);
                }
            }

            // Restore ERP tasks
            if (! empty($data['erp']['tasks']) && class_exists(ERPTask::class)) {
                foreach ($data['erp']['tasks'] as $task) {
                    ERPTask::insert($task);
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tenant Import Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
