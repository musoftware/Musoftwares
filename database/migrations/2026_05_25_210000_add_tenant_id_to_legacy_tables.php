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
        // 1. Add tenant_id to `erp_todo_items`
        if (Schema::hasTable('erp_todo_items') && !Schema::hasColumn('erp_todo_items', 'tenant_id')) {
            Schema::table('erp_todo_items', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_todo_items') && Schema::hasColumn('erp_todo_items', 'tenant_id')) {
            Schema::table('erp_todo_items', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
