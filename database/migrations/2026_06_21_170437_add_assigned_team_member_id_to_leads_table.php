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
        // The leads table belongs to the separated CRM project and may not exist in this monolith.
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'assigned_team_member_id')) {
                $table->foreignId('assigned_team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'assigned_team_member_id')) {
                $table->dropForeign(['assigned_team_member_id']);
                $table->dropColumn('assigned_team_member_id');
            }
        });
    }
};
