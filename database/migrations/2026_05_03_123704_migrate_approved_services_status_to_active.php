<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migrate all services with status='approved' to status='active'.
     *
     * Background: The bulk-approve action previously set status to 'approved'
     * while the single-approve action set it to 'active'. Both mean the same
     * thing. This migration normalises all legacy 'approved' records to 'active'.
     */
    public function up(): void
    {
        $count = DB::table('services')
            ->where('status', 'approved')
            ->count();

        if ($count === 0) {
            Log::info('[Migration] No services with status=approved found. Nothing to do.');
            return;
        }

        DB::table('services')
            ->where('status', 'approved')
            ->update(['status' => 'active']);

        Log::info("[Migration] Migrated {$count} service(s) from status='approved' to status='active'.");
    }

    /**
     * Rollback: restore 'active' services that were originally 'approved'.
     *
     * NOTE: This is a best-effort rollback. Since both states are functionally
     * identical, rolling back only affects services that were previously 'approved'
     * AND still carry the approved_at timestamp but NOT a suspended_at/rejected_at.
     * Services manually activated after approval will NOT be rolled back.
     */
    public function down(): void
    {
        // We cannot perfectly identify which 'active' records were originally
        // 'approved', so we do nothing on rollback to avoid data corruption.
        // Re-run the old bulk-approve logic if you truly need to revert.
    }
};
