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
        $indexesFound = collect(Schema::getIndexes('leads'))->pluck('name')->toArray();

        Schema::table('leads', function (Blueprint $table) use ($indexesFound) {
            if (!in_array('leads_tenant_phone_unique', $indexesFound)) {
                $table->unique(['workspace_id', 'phone'], 'leads_tenant_phone_unique');
            }

            if (!in_array('leads_kanban_idx', $indexesFound)) {
                $table->index(['workspace_id', 'status', 'assigned_to'], 'leads_kanban_idx');
            }
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
