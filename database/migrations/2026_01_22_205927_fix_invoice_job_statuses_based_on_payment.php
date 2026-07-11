<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update invoices that are fully paid to have job_status = 'done'
        // This ensures historical consistency
        DB::table('invoices')
            ->where('status', 'paid')
            ->update(['job_status' => 'done']);

        // Update invoices that are partially paid and pending to be processing
        DB::table('invoices')
            ->where('status', 'partially_paid')
            ->where('job_status', 'pending')
            ->update(['job_status' => 'processing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration updates data based on logical consistency, so reverting
        // would require knowing the exact previous state which isn't tracked.
        // We can optionally set paid/partially_paid back to pending if needed,
        // but that creates data loss of valid states.
    }
};
