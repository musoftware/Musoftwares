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
            'transactions' => \Modules\ERP\Models\WalletTransaction::where('tenant_id', $tenant->id)->get()->toArray(),
            
            // Addons Support (Check table existence)
            'products' => \Illuminate\Support\Facades\Schema::hasTable('erp_products') ? \Modules\ERP\Models\Product::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'product_stock_logs' => \Illuminate\Support\Facades\Schema::hasTable('erp_product_stock_logs') ? \Modules\ERP\Models\ProductStockLog::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'expenses' => \Illuminate\Support\Facades\Schema::hasTable('erp_expenses') ? \Modules\ERP\Models\Expense::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'expense_transactions' => \Illuminate\Support\Facades\Schema::hasTable('erp_expense_transactions') ? \Modules\ERP\Models\ExpenseTransaction::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'support_tickets' => \Illuminate\Support\Facades\Schema::hasTable('erp_support_tickets') ? \Modules\ERP\Models\SupportTicket::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'contracts' => \Illuminate\Support\Facades\Schema::hasTable('erp_contracts') ? \Modules\ERP\Models\Contract::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'referral_earnings' => \Illuminate\Support\Facades\Schema::hasTable('erp_referral_earnings') ? \Modules\ERP\Models\ReferralEarning::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'withdrawal_requests' => \Illuminate\Support\Facades\Schema::hasTable('erp_withdrawal_requests') ? \Modules\ERP\Models\WithdrawalRequest::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'withdrawals' => \Illuminate\Support\Facades\Schema::hasTable('erp_withdrawals') ? \Modules\ERP\Models\Withdrawal::where('tenant_id', $tenant->id)->get()->toArray() : [],
            'timer_sessions' => \Illuminate\Support\Facades\Schema::hasTable('erp_timer_sessions') ? \Modules\ERP\Models\TimerSession::where('tenant_id', $tenant->id)->get()->toArray() : [],
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

        // Convert ISO 8601 dates to MySQL compatible datetime strings
        // and enforce dynamic security keys (tenant_id, user_id, created_by)
        // to prevent ID tampering from the backup file.
        array_walk_recursive($data, function (&$value, $key) use ($tenant) {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $value)) {
                $value = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            }
            if ($key === 'tenant_id') {
                $value = $tenant->id;
            }
            if (in_array($key, ['user_id', 'created_by']) && $value !== null) {
                $value = $tenant->user_id;
            }
        });

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
            
            // Delete addon dependent records
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_expense_transactions')) \Modules\ERP\Models\ExpenseTransaction::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_product_stock_logs')) \Modules\ERP\Models\ProductStockLog::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_referral_earnings')) \Modules\ERP\Models\ReferralEarning::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_timer_sessions')) \Modules\ERP\Models\TimerSession::where('tenant_id', $tenant->id)->delete();

            // Delete primary records
            \Modules\ERP\Models\Invoice::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\ERPTask::where('tenant_id', $tenant->id)->delete();
            // ClientWallet model removed — balance is computed from transactions
            \Modules\ERP\Models\RecurringEntry::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\PaymentMethod::where('tenant_id', $tenant->id)->delete();
            \Modules\ERP\Models\TenantNote::where('tenant_id', $tenant->id)->delete();
            
            // Delete addon primary records
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_expenses')) \Modules\ERP\Models\Expense::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_products')) \Modules\ERP\Models\Product::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_support_tickets')) \Modules\ERP\Models\SupportTicket::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_contracts')) \Modules\ERP\Models\Contract::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_withdrawal_requests')) \Modules\ERP\Models\WithdrawalRequest::where('tenant_id', $tenant->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('erp_withdrawals')) \Modules\ERP\Models\Withdrawal::where('tenant_id', $tenant->id)->delete();

            // Finally delete clients
            \Modules\ERP\Models\TenantClient::where('tenant_id', $tenant->id)->delete();

            // ID Maps to remap foreign keys since we cannot preserve IDs in a shared DB
            $maps = [
                'client_id' => [],
                'payment_method_id' => [],
                'project_id' => [],
                'invoice_id' => [],
                'invoice_cost_id' => [],
                'invoice_item_id' => [],
                'task_id' => [],
                'product_id' => [],
                'expense_id' => [],
                'contract_id' => [],
                'support_ticket_id' => [],
            ];

            $remapForeignKeys = function(&$item) use (&$maps) {
                foreach (array_keys($maps) as $fk) {
                    if (isset($item[$fk]) && isset($maps[$fk][$item[$fk]])) {
                        $item[$fk] = $maps[$fk][$item[$fk]];
                    }
                }
            };

            if (isset($data['clients'])) {
                foreach ($data['clients'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['client_id'][$oldId] = DB::table('erp_tenant_clients')->insertGetId($item);
                }
            }
            if (isset($data['payment_methods'])) {
                foreach ($data['payment_methods'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['payment_method_id'][$oldId] = DB::table('erp_payment_methods')->insertGetId($item);
                }
            }
            if (isset($data['projects'])) {
                foreach ($data['projects'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['project_id'][$oldId] = DB::table('erp_projects')->insertGetId($item);
                }
            }
            
            // Addons Primary
            if (isset($data['products'])) {
                foreach ($data['products'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['product_id'][$oldId] = DB::table('erp_products')->insertGetId($item);
                }
            }
            if (isset($data['expenses'])) {
                foreach ($data['expenses'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['expense_id'][$oldId] = DB::table('erp_expenses')->insertGetId($item);
                }
            }
            if (isset($data['contracts'])) {
                foreach ($data['contracts'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['contract_id'][$oldId] = DB::table('erp_contracts')->insertGetId($item);
                }
            }
            if (isset($data['support_tickets'])) {
                foreach ($data['support_tickets'] as $item) {
                    $oldId = $item['id'];
                    unset($item['id']);
                    $remapForeignKeys($item);
                    $maps['support_ticket_id'][$oldId] = DB::table('erp_support_tickets')->insertGetId($item);
                }
            }

            if (isset($data['tenant_notes'])) {
                foreach ($data['tenant_notes'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_tenant_notes')->insert($item);
                }
            }

            if (isset($data['invoices'])) {
                foreach ($data['invoices'] as $invoiceData) {
                    $oldId = $invoiceData['id'];
                    unset($invoiceData['id']);
                    
                    $items = $invoiceData['items'] ?? [];
                    $costs = $invoiceData['costs'] ?? [];
                    unset($invoiceData['items'], $invoiceData['costs']);

                    $remapForeignKeys($invoiceData);

                    $newInvoiceId = DB::table('erp_invoices')->insertGetId($invoiceData);
                    $maps['invoice_id'][$oldId] = $newInvoiceId;
                    
                    if (count($items) > 0) {
                        foreach ($items as &$i) {
                            $oldItemId = $i['id'] ?? null;
                            unset($i['id']);
                            $i['invoice_id'] = $newInvoiceId;
                            $remapForeignKeys($i);
                            $newItemId = DB::table('erp_invoice_items')->insertGetId($i);
                            if ($oldItemId) {
                                $maps['invoice_item_id'][$oldItemId] = $newItemId;
                            }
                        }
                    }
                    if (count($costs) > 0) {
                        foreach ($costs as &$c) {
                            $oldCostId = $c['id'] ?? null;
                            unset($c['id']);
                            $c['invoice_id'] = $newInvoiceId;
                            $remapForeignKeys($c);
                            $newCostId = DB::table('erp_invoice_costs')->insertGetId($c);
                            if ($oldCostId) {
                                $maps['invoice_cost_id'][$oldCostId] = $newCostId;
                            }
                        }
                    }
                }
            }

            if (isset($data['tasks'])) {
                foreach ($data['tasks'] as $taskData) {
                    $oldId = $taskData['id'];
                    unset($taskData['id']);
                    
                    $items = $taskData['items'] ?? [];
                    unset($taskData['items']);

                    $remapForeignKeys($taskData);

                    $newTaskId = DB::table('erp_tasks')->insertGetId($taskData);
                    $maps['task_id'][$oldId] = $newTaskId;
                    
                    if (count($items) > 0) {
                        foreach ($items as &$item) {
                            unset($item['id']);
                            $item['task_id'] = $newTaskId;
                            $remapForeignKeys($item);
                            if (isset($item['tags']) && is_array($item['tags'])) {
                                $item['tags'] = json_encode($item['tags']);
                            }
                            DB::table('erp_todo_items')->insert($item);
                        }
                    }
                }
            }
            
            if (isset($data['recurring_entries'])) {
                foreach ($data['recurring_entries'] as $item) {
                    unset($item['id'], $item['last_execution']);
                    $remapForeignKeys($item);
                    DB::table('erp_recurring_entries')->insert($item);
                }
            }

            if (isset($data['client_notes'])) {
                foreach ($data['client_notes'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_client_notes')->insert($item);
                }
            }
            if (isset($data['transactions'])) {
                foreach ($data['transactions'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    
                    if (isset($item['reference_type']) && isset($item['reference_id'])) {
                        if (str_contains($item['reference_type'], 'Invoice') && isset($maps['invoice_id'][$item['reference_id']])) {
                            $item['reference_id'] = $maps['invoice_id'][$item['reference_id']];
                        } elseif (str_contains($item['reference_type'], 'Project') && isset($maps['project_id'][$item['reference_id']])) {
                            $item['reference_id'] = $maps['project_id'][$item['reference_id']];
                        }
                    }
                    DB::table('erp_client_transactions')->insert($item);
                }
            }
            
            // Addon Dependents
            if (isset($data['product_stock_logs']) && \Illuminate\Support\Facades\Schema::hasTable('erp_product_stock_logs')) {
                foreach ($data['product_stock_logs'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_product_stock_logs')->insert($item);
                }
            }
            if (isset($data['expense_transactions']) && \Illuminate\Support\Facades\Schema::hasTable('erp_expense_transactions')) {
                foreach ($data['expense_transactions'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_expense_transactions')->insert($item);
                }
            }
            if (isset($data['referral_earnings']) && \Illuminate\Support\Facades\Schema::hasTable('erp_client_referral_earnings')) {
                foreach ($data['referral_earnings'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    if (isset($item['referrer_id']) && isset($maps['client_id'][$item['referrer_id']])) {
                        $item['referrer_id'] = $maps['client_id'][$item['referrer_id']];
                    }
                    if (isset($item['referee_id']) && isset($maps['client_id'][$item['referee_id']])) {
                        $item['referee_id'] = $maps['client_id'][$item['referee_id']];
                    }
                    DB::table('erp_client_referral_earnings')->insert($item);
                }
            }
            if (isset($data['withdrawals']) && \Illuminate\Support\Facades\Schema::hasTable('erp_withdrawals')) {
                foreach ($data['withdrawals'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_withdrawals')->insert($item);
                }
            }
            if (isset($data['withdrawal_requests']) && \Illuminate\Support\Facades\Schema::hasTable('erp_withdrawal_requests')) {
                foreach ($data['withdrawal_requests'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_withdrawal_requests')->insert($item);
                }
            }
            if (isset($data['timer_sessions']) && \Illuminate\Support\Facades\Schema::hasTable('erp_timer_sessions')) {
                foreach ($data['timer_sessions'] as $item) {
                    unset($item['id']);
                    $remapForeignKeys($item);
                    DB::table('erp_timer_sessions')->insert($item);
                }
            }
        });
    }
}
