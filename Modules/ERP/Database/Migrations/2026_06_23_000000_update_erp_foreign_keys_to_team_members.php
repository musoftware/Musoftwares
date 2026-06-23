<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. erp_expenses
        if (Schema::hasTable('erp_expenses')) {
            Schema::table('erp_expenses', function (Blueprint $table) {
                if (Schema::hasColumn('erp_expenses', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }

        // 2. erp_tenant_notes
        if (Schema::hasTable('erp_tenant_notes')) {
            Schema::table('erp_tenant_notes', function (Blueprint $table) {
                if (Schema::hasColumn('erp_tenant_notes', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }

        // 3. erp_projects
        if (Schema::hasTable('erp_projects')) {
            Schema::table('erp_projects', function (Blueprint $table) {
                if (Schema::hasColumn('erp_projects', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
        
        if (Schema::hasTable('erp_project_members')) {
            Schema::table('erp_project_members', function (Blueprint $table) {
                if (Schema::hasColumn('erp_project_members', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }

        // 4. erp_tasks
        if (Schema::hasTable('erp_tasks')) {
            Schema::table('erp_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('erp_tasks', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }

        // 5. erp_workflows
        if (Schema::hasTable('erp_workflows')) {
            Schema::table('erp_workflows', function (Blueprint $table) {
                if (Schema::hasColumn('erp_workflows', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
        
        if (Schema::hasTable('erp_approval_requests')) {
            Schema::table('erp_approval_requests', function (Blueprint $table) {
                if (Schema::hasColumn('erp_approval_requests', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }

        // 6. core erp tables (invoices, wallet_transactions, etc.)
        if (Schema::hasTable('erp_invoices')) {
            Schema::table('erp_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('erp_invoices', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
        
        if (Schema::hasTable('erp_recurring_entries')) {
            Schema::table('erp_recurring_entries', function (Blueprint $table) {
                if (Schema::hasColumn('erp_recurring_entries', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
        
        if (Schema::hasTable('erp_wallet_transactions')) {
            Schema::table('erp_wallet_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('erp_wallet_transactions', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
        
        if (Schema::hasTable('erp_withdrawals')) {
            Schema::table('erp_withdrawals', function (Blueprint $table) {
                if (Schema::hasColumn('erp_withdrawals', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would restore references to 'users' table
        $tables = [
            'erp_expenses', 'erp_tenant_notes', 'erp_projects', 'erp_project_members',
            'erp_tasks', 'erp_workflows', 'erp_approval_requests', 'erp_invoices',
            'erp_recurring_entries', 'erp_wallet_transactions', 'erp_withdrawals'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        $table->dropForeign(['created_by']);
                        $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                    }
                });
            }
        }
    }
};
