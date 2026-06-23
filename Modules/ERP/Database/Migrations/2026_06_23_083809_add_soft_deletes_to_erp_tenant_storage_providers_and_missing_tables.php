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
        if (!Schema::hasColumn('erp_client_notes', 'deleted_at')) {
            Schema::table('erp_client_notes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if (!Schema::hasColumn('erp_tenant_storage_providers', 'deleted_at')) {
            Schema::table('erp_tenant_storage_providers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('erp_client_notes', 'deleted_at')) {
            Schema::table('erp_client_notes', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        
        if (Schema::hasColumn('erp_tenant_storage_providers', 'deleted_at')) {
            Schema::table('erp_tenant_storage_providers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
