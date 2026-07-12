<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the time-only `appearance_time` column with a full datetime
     * `published_at` column on `project_board_items`.
     *
     * Semantics: when set, the card is hidden from guests/clients until the
     * stored datetime arrives (same "publish-gate" already used by reports).
     * Null means "always visible" (legacy behaviour).
     */
    public function up(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropColumn('appearance_time');
        });

        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dateTime('published_at')->nullable()->after('lane');
        });
    }

    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });

        Schema::table('project_board_items', function (Blueprint $table) {
            $table->time('appearance_time')->nullable()->after('lane');
        });
    }
};
