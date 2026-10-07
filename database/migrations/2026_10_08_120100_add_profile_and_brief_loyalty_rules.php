<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The client portal promises 50 points for a 100% profile and 100 points for a submitted
 * project brief, but no rule rows existed, so LoyaltyService silently awarded nothing.
 */
return new class extends Migration
{
    private const RULES = [
        'profile_completed' => 50,
        'brief_submitted' => 100,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('loyalty_rules')) {
            return;
        }

        foreach (self::RULES as $eventType => $points) {
            if (DB::table('loyalty_rules')->where('event_type', $eventType)->exists()) {
                continue;
            }

            DB::table('loyalty_rules')->insert([
                'event_type' => $eventType,
                'base_points' => $points,
                'conditions_payload' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('loyalty_rules')->whereIn('event_type', array_keys(self::RULES))->delete();
    }
};
