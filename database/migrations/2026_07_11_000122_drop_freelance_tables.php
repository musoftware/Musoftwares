<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop all `freelance_*` tables and remove `can_add_freelance_skills`
     * from `users`. Idempotent: each drop guards on hasTable()/hasColumn().
     *
     * NOTE: The earlier migration 2026_06_24_134855_remove_old_modules_tables.php
     * already drops tables with the `freelance_` prefix on `up()`, so this
     * migration is a safety net for environments that ran only the structural
     * Freelance migrations but not that cleanup migration.
     */
    public function up(): void
    {
        $tables = [
            'freelance_user_skills',
            'freelance_skills',
            'freelance_profiles',
            'freelance_jobs',
            'freelance_proposals',
            'freelance_contracts',
            'freelance_reviews',
            'freelance_points',
            'freelance_pokes',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
        Schema::enableForeignKeyConstraints();

        if (Schema::hasColumn('users', 'can_add_freelance_skills')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_add_freelance_skills');
            });
        }
    }

    public function down(): void
    {
        // No down migration (data-destructive).
    }
};
