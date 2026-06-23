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
        if (!Schema::hasColumn('erp_client_transactions', 'deleted_at')) {
            Schema::table('erp_client_transactions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if (!Schema::hasColumn('erp_expense_transactions', 'deleted_at')) {
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('erp_recurring_execution_logs', 'deleted_at')) {
            Schema::table('erp_recurring_execution_logs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('erp_client_transactions', 'deleted_at')) {
            Schema::table('erp_client_transactions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('erp_expense_transactions', 'deleted_at')) {
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('erp_recurring_execution_logs', 'deleted_at')) {
            Schema::table('erp_recurring_execution_logs', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
