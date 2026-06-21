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
            if (!Schema::hasColumn('leads', 'assigned_team_member_id')) {
                $table->foreignId('assigned_team_member_id')->nullable()->constrained('crm_team_members')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'assigned_team_member_id')) {
                $table->dropForeign(['assigned_team_member_id']);
                $table->dropColumn('assigned_team_member_id');
            }
        });
    }
};
