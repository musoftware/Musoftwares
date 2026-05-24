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
        if (Schema::hasTable('erp_invoices') && !Schema::hasColumn('erp_invoices', 'project_id')) {
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('erp_projects')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_invoices') && Schema::hasColumn('erp_invoices', 'project_id')) {
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }
    }
};
