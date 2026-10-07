<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure due_date column exists on invoices
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'due_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('status');
            });
        }

        // Add the overdue invoice penalty loyalty rule
        $existing = DB::table('loyalty_rules')
            ->where('event_type', 'invoice_overdue_penalty')
            ->exists();

        if (! $existing) {
            DB::table('loyalty_rules')->insert([
                'event_type'         => 'invoice_overdue_penalty',
                'base_points'        => 1,
                'conditions_payload' => json_encode([
                    'egp_per_point'          => 100,
                    'min_points_per_day'     => 1,
                    'affect_lifetime_points' => true,
                ]),
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('loyalty_rules')
            ->where('event_type', 'invoice_overdue_penalty')
            ->delete();

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'due_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('due_date');
            });
        }
    }
};
