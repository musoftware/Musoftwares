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
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('workspace_id')->constrained('crm_branches')->nullOnDelete();
            });
        }

        if (Schema::hasTable('crm_whatsapp_conversations')) {
            Schema::table('crm_whatsapp_conversations', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('workspace_id')->constrained('crm_branches')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('crm_whatsapp_conversations')) {
            Schema::table('crm_whatsapp_conversations', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
