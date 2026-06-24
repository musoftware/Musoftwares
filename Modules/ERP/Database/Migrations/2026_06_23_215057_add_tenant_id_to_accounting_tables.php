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
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
