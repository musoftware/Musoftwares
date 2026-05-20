<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function addIndexSafely($table, $columns, $name = null)
    {
        $columnsArr = (array) $columns;
        $indexName = $name ?: strtolower($table . '_' . implode('_', $columnsArr) . '_index');

        $exists = false;
        if (DB::getDriverName() === 'mysql') {
            $exists = collect(DB::select("SHOW INDEXES FROM {$table}"))->contains('Key_name', $indexName);
        }

        if (!$exists) {
            try {
                Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                    $t->index($columns, $indexName);
                });
            } catch (\Exception $e) {
                // Ignore if it already exists or if column doesn't exist
            }
        }
    }

    private function dropIndexSafely($table, $columns, $name = null)
    {
        $columnsArr = (array) $columns;
        $indexName = $name ?: strtolower($table . '_' . implode('_', $columnsArr) . '_index');

        $exists = false;
        if (DB::getDriverName() === 'mysql') {
            $exists = collect(DB::select("SHOW INDEXES FROM {$table}"))->contains('Key_name', $indexName);
        } else {
            // For sqlite in down() we just try to drop and catch
            $exists = true; 
        }

        if ($exists) {
            try {
                Schema::table($table, function (Blueprint $t) use ($indexName) {
                    $t->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
        }
    }

    public function up(): void
    {
        if (Schema::hasTable('users')) {
            $this->addIndexSafely('users', 'onboarding_completed');
        }

        if (Schema::hasTable('tenant_clients')) {
            $this->addIndexSafely('tenant_clients', 'email');
            $this->addIndexSafely('tenant_clients', 'user_id');
            $this->addIndexSafely('tenant_clients', ['tenant_id', 'status']);
        }

        if (Schema::hasTable('invoices')) {
            $this->addIndexSafely('invoices', 'status');
            $this->addIndexSafely('invoices', ['tenant_id', 'status']);
            $this->addIndexSafely('invoices', 'due_date');
        }

        if (Schema::hasTable('journal_entries')) {
            $this->addIndexSafely('journal_entries', 'date');
            $this->addIndexSafely('journal_entries', ['reference_type', 'reference_id']);
        }
        
        if (Schema::hasTable('journal_entry_lines')) {
            $this->addIndexSafely('journal_entry_lines', 'journal_entry_id');
            $this->addIndexSafely('journal_entry_lines', 'account_id');
        }

        if (Schema::hasTable('recurring_entries')) {
            $this->addIndexSafely('recurring_entries', ['tenant_id', 'is_active', 'next_run_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            $this->dropIndexSafely('users', 'onboarding_completed');
        }

        if (Schema::hasTable('tenant_clients')) {
            $this->dropIndexSafely('tenant_clients', 'email');
            $this->dropIndexSafely('tenant_clients', 'user_id');
            $this->dropIndexSafely('tenant_clients', ['tenant_id', 'status']);
        }

        if (Schema::hasTable('invoices')) {
            $this->dropIndexSafely('invoices', 'status');
            $this->dropIndexSafely('invoices', ['tenant_id', 'status']);
            $this->dropIndexSafely('invoices', 'due_date');
        }

        if (Schema::hasTable('journal_entries')) {
            $this->dropIndexSafely('journal_entries', 'date');
            $this->dropIndexSafely('journal_entries', ['reference_type', 'reference_id']);
        }
        
        if (Schema::hasTable('journal_entry_lines')) {
            $this->dropIndexSafely('journal_entry_lines', 'journal_entry_id');
            $this->dropIndexSafely('journal_entry_lines', 'account_id');
        }

        if (Schema::hasTable('recurring_entries')) {
            $this->dropIndexSafely('recurring_entries', ['tenant_id', 'is_active', 'next_run_at']);
        }
    }
};
