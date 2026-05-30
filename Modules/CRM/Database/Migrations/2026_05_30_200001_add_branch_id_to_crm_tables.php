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
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workspace_id')->constrained('crm_branches')->nullOnDelete();
        });

        Schema::table('crm_whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workspace_id')->constrained('crm_branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('crm_whatsapp_conversations', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
