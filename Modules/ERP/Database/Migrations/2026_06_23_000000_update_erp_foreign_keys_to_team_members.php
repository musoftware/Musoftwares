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
        $tables = [
            'erp_expenses', 'erp_tenant_notes', 'erp_projects', 'erp_project_members',
            'erp_tasks', 'erp_workflows', 'erp_approval_requests', 'erp_invoices',
            'erp_recurring_entries', 'erp_wallet_transactions', 'erp_withdrawals'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (Schema::hasColumn($tableName, 'created_by')) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->dropForeign(['created_by']);
                        });
                    } catch (\Exception $e) {
                        // Ignore exception if foreign key doesn't exist
                    }

                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->foreign('created_by')->references('id')->on('erp_team_members')->nullOnDelete();
                        });
                    } catch (\Exception $e) {
                        // Ignore exception if foreign key already exists
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'erp_expenses', 'erp_tenant_notes', 'erp_projects', 'erp_project_members',
            'erp_tasks', 'erp_workflows', 'erp_approval_requests', 'erp_invoices',
            'erp_recurring_entries', 'erp_wallet_transactions', 'erp_withdrawals'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (Schema::hasColumn($tableName, 'created_by')) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->dropForeign(['created_by']);
                        });
                    } catch (\Exception $e) {
                        // Ignore if it doesn't exist
                    }

                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                        });
                    } catch (\Exception $e) {
                        // Ignore if it already exists
                    }
                }
            }
        }
    }
};
