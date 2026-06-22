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
        if (!Schema::hasColumn('erp_tasks', 'assigned_team_member_id')) {
            Schema::table('erp_tasks', function (Blueprint $table) {
                $table->foreignId('assigned_team_member_id')->nullable()->constrained('erp_team_members')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('erp_todo_items', 'assigned_team_member_id')) {
            Schema::table('erp_todo_items', function (Blueprint $table) {
                $table->foreignId('assigned_team_member_id')->nullable()->constrained('erp_team_members')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('erp_todo_items', 'assigned_team_member_id')) {
            Schema::table('erp_todo_items', function (Blueprint $table) {
                $table->dropForeign(['assigned_team_member_id']);
                $table->dropColumn('assigned_team_member_id');
            });
        }

        if (Schema::hasColumn('erp_tasks', 'assigned_team_member_id')) {
            Schema::table('erp_tasks', function (Blueprint $table) {
                $table->dropForeign(['assigned_team_member_id']);
                $table->dropColumn('assigned_team_member_id');
            });
        }
    }
};
