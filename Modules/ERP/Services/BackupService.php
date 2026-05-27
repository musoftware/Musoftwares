<?php

namespace Modules\ERP\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupService
{
    public function createBackup($tenant)
    {
        $data = [
            'clients' => \Modules\ERP\Models\TenantClient::where('tenant_id', $tenant->id)->get()->toArray(),
            'invoices' => \Modules\ERP\Models\Invoice::where('tenant_id', $tenant->id)->with('items', 'costs')->get()->toArray(),
            'tasks' => \Modules\ERP\Models\ERPTask::where('tenant_id', $tenant->id)->with('items')->get()->toArray(),
            'projects' => \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->get()->toArray(),
            'payment_methods' => \Modules\ERP\Models\PaymentMethod::where('tenant_id', $tenant->id)->get()->toArray(),
            'recurring_entries' => \Modules\ERP\Models\RecurringEntry::where('tenant_id', $tenant->id)->get()->toArray(),
            'tenant_notes' => \Modules\ERP\Models\TenantNote::where('tenant_id', $tenant->id)->get()->toArray(),
            'client_notes' => \Modules\ERP\Models\ClientNote::whereHas('client', function($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            })->get()->toArray(),
            'wallets' => \Modules\ERP\Models\ClientWallet::where('tenant_id', $tenant->id)->get()->toArray(),
            'wallet_transactions' => \Modules\ERP\Models\WalletTransaction::where('tenant_id', $tenant->id)->get()->toArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        $fileName = 'erp_backup_' . $tenant->id . '_' . now()->format('Y_m_d_H_i_s') . '.json';
        $path = storage_path('app/backups');
        
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        
        $filePath = $path . '/' . $fileName;
        File::put($filePath, $json);
        
        return $filePath;
    }

    public function restoreBackup($tenant, $file)
    {
        $json = File::get($file->getRealPath());
        $data = json_decode($json, true);

        if (!$data) {
            throw new \Exception('Invalid backup file format.');
        }

        DB::transaction(function () use ($tenant, $data) {
            // Delete dependent records first
            \Modules\ERP\Models\InvoiceItem::whereHas('invoice', function($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            })->delete();
            \Modules\ERP\Models\InvoiceCost::whereHas('invoice', function($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            })->delete();
            \Modules\ERP\Models\WalletTransaction::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\ERPTodoItem::whereHas('task', function($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            })->delete();
            \Modules\ERP\Models\ClientNote::whereHas('client', function($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            })->delete();

            // Delete primary records
            \Modules\ERP\Models\Invoice::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\ERPTask::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\ClientWallet::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\RecurringEntry::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\PaymentMethod::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\TenantNote::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\TenantClient::where('tenant_id', $tenant->id)->delete();

            // Insert records using DB::table to preserve IDs
            if (isset($data['clients']) && count($data['clients']) > 0) {
                DB::table('tenant_clients')->insert($data['clients']);
            }
            if (isset($data['payment_methods']) && count($data['payment_methods']) > 0) {
                DB::table('payment_methods')->insert($data['payment_methods']);
            }
            if (isset($data['projects']) && count($data['projects']) > 0) {
                DB::table('projects')->insert($data['projects']);
            }
            if (isset($data['wallets']) && count($data['wallets']) > 0) {
                DB::table('client_wallets')->insert($data['wallets']);
            }
            if (isset($data['tenant_notes']) && count($data['tenant_notes']) > 0) {
                DB::table('tenant_notes')->insert($data['tenant_notes']);
            }

            if (isset($data['invoices'])) {
                foreach ($data['invoices'] as $invoiceData) {
                    $items = $invoiceData['items'] ?? [];
                    $costs = $invoiceData['costs'] ?? [];
                    unset($invoiceData['items']);
                    unset($invoiceData['costs']);
                    DB::table('invoices')->insert($invoiceData);
                    
                    if (count($items) > 0) {
                        DB::table('invoice_items')->insert($items);
                    }
                    if (count($costs) > 0) {
                        DB::table('invoice_costs')->insert($costs);
                    }
                }
            }

            if (isset($data['tasks'])) {
                foreach ($data['tasks'] as $taskData) {
                    $items = $taskData['items'] ?? [];
                    unset($taskData['items']);
                    DB::table('erp_tasks')->insert($taskData);
                    
                    if (count($items) > 0) {
                        // Cast JSON array to string before insert for 'tags' if it's an array
                        foreach ($items as &$item) {
                            if (isset($item['tags']) && is_array($item['tags'])) {
                                $item['tags'] = json_encode($item['tags']);
                            }
                        }
                        DB::table('erp_todo_items')->insert($items);
                    }
                }
            }
            
            if (isset($data['recurring_entries']) && count($data['recurring_entries']) > 0) {
                // Remove some potential appended attributes
                foreach ($data['recurring_entries'] as &$entry) {
                    unset($entry['last_execution']);
                }
                DB::table('recurring_entries')->insert($data['recurring_entries']);
            }

            if (isset($data['client_notes']) && count($data['client_notes']) > 0) {
                DB::table('client_notes')->insert($data['client_notes']);
            }
            if (isset($data['wallet_transactions']) && count($data['wallet_transactions']) > 0) {
                // Some casts might be array, like metadata
                foreach ($data['wallet_transactions'] as &$wt) {
                    if (isset($wt['metadata']) && is_array($wt['metadata'])) {
                        $wt['metadata'] = json_encode($wt['metadata']);
                    }
                }
                DB::table('wallet_transactions')->insert($data['wallet_transactions']);
            }
        });
    }
}
