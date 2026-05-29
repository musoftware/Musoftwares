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
        Schema::table('erp_expenses', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('tenant_id')->constrained('erp_tenant_clients')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('client_id')->constrained('erp_projects')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_expenses', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['client_id', 'project_id']);
        });
    }
};
