<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('loyalty_rules')
            ->whereIn('event_type', ['monthly_spend_10k', 'monthly_spend_15k'])
            ->pluck('event_type')
            ->toArray();

        $rules = [];

        if (! in_array('monthly_spend_10k', $existing, true)) {
            $rules[] = [
                'event_type'         => 'monthly_spend_10k',
                'base_points'        => 600,
                'conditions_payload' => json_encode(['threshold_egp' => 10000]),
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        if (! in_array('monthly_spend_15k', $existing, true)) {
            $rules[] = [
                'event_type'         => 'monthly_spend_15k',
                'base_points'        => 1000,
                'conditions_payload' => json_encode(['threshold_egp' => 15000]),
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        if (! empty($rules)) {
            DB::table('loyalty_rules')->insert($rules);
        }
    }

    public function down(): void
    {
        DB::table('loyalty_rules')
            ->whereIn('event_type', ['monthly_spend_10k', 'monthly_spend_15k'])
            ->delete();
    }
};
