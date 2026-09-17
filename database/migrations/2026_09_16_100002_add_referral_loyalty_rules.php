<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('loyalty_rules')->whereIn('event_type', ['referral_registered', 'referral_first_payment'])->pluck('event_type')->toArray();

        $rules = [];

        if (! in_array('referral_registered', $existing, true)) {
            $rules[] = [
                'event_type'         => 'referral_registered',
                'base_points'        => 100,
                'conditions_payload' => null,
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        if (! in_array('referral_first_payment', $existing, true)) {
            $rules[] = [
                'event_type'         => 'referral_first_payment',
                'base_points'        => 250,
                'conditions_payload' => null,
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
        DB::table('loyalty_rules')->whereIn('event_type', ['referral_registered', 'referral_first_payment'])->delete();
    }
};
