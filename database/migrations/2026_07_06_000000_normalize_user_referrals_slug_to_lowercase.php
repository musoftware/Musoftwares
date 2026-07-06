<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data migration: normalise existing user_referrals.slug values to
 * lowercase so the case-sensitive utf8_cs collation doesn't cause unique-rule
 * false negatives when a user later tries to claim a mixed-case slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_referrals')
            ->whereNotNull('slug')
            ->where('slug', '!=', DB::raw('LOWER(slug)'))
            ->update(['slug' => DB::raw('LOWER(slug)')]);
    }

    public function down(): void
    {
        // No rollback — original casing is lost after this migration runs.
    }
};