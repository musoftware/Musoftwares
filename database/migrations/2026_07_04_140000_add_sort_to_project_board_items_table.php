<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            // Per-day, per-lane ordering. Lower = earlier in the board.
            // Existing rows get sort=0; subsequent place() calls assign monotonically increasing values
            // within (project_id, for_date, lane).
            $table->unsignedInteger('sort')->default(0)->after('lane');
            $table->index(['project_id', 'for_date', 'lane', 'sort'], 'pbi_ordering_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropIndex('pbi_ordering_idx');
            $table->dropColumn('sort');
        });
    }
};
