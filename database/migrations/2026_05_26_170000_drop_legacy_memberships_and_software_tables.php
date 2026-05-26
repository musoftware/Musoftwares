<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the legacy Membership + Software Programs system.
 *
 * These tables were part of the old /admin/memberships/software flow
 * and have been fully superseded by the new Tools marketplace system
 * (/admin/tools). Safe to remove — no application code references them.
 *
 * Uses FOREIGN_KEY_CHECKS=0 to bypass any remaining FK references
 * that may exist from data migrations or legacy pivot tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        Schema::dropIfExists('software_program_translations');
        Schema::dropIfExists('membership_programs');
        Schema::dropIfExists('software_custom_values');
        Schema::dropIfExists('pc_serials');
        Schema::dropIfExists('membership_users');
        Schema::dropIfExists('software_programs');
        Schema::dropIfExists('memberships');

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        // These tables are intentionally not restored.
        // The new Tools system (/admin/tools) replaces them entirely.
    }
};
