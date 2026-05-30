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
        Schema::table('leads', function (Blueprint $table) {
            // Hardware-level idempotency protection
            $table->unique(['tenant_id', 'phone'], 'leads_tenant_phone_unique');

            // High-speed Kanban fetch index
            $table->index(['tenant_id', 'pipeline_stage', 'assigned_to_id'], 'leads_kanban_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_tenant_phone_unique');
            $table->dropIndex('leads_kanban_idx');
        });
    }
};
