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
        Schema::table('erp_support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('erp_support_tickets', 'assigned_team_member_id')) {
                $table->foreignId('assigned_team_member_id')->nullable()->constrained('erp_team_members')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('erp_support_tickets', 'assigned_team_member_id')) {
                $table->dropForeign(['assigned_team_member_id']);
                $table->dropColumn('assigned_team_member_id');
            }
        });
    }
};
