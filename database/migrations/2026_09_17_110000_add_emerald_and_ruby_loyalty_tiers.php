<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            // Re-order existing tiers to fit Emerald and Ruby cleanly
            DB::table('loyalty_tiers')->where('slug', 'bronze')->update([
                'order_index' => 1,
            ]);

            DB::table('loyalty_tiers')->where('slug', 'silver')->update([
                'order_index' => 2,
            ]);

            DB::table('loyalty_tiers')->where('slug', 'gold')->update([
                'order_index' => 3,
            ]);

            // Add Emerald (1,000 PTS)
            $emeraldExists = DB::table('loyalty_tiers')->where('slug', 'emerald')->exists();
            if (! $emeraldExists) {
                DB::table('loyalty_tiers')->insert([
                    'name' => 'Emerald',
                    'slug' => 'emerald',
                    'min_lifetime_points' => 1000,
                    'discount_percentage' => 12.00,
                    'ticket_priority_level' => 'priority',
                    'badge_color' => '#10B981',
                    'order_index' => 4,
                    'is_active' => true,
                    'perks_payload' => json_encode([
                        'perks' => [
                            '12% Automatic Invoice Deduction',
                            'Priority Dispatch & Technical Routing',
                            'Bi-Weekly Architecture Sync Calls',
                            'Extended Deliverables Warranty',
                        ],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('loyalty_tiers')->where('slug', 'emerald')->update([
                    'order_index' => 4,
                    'min_lifetime_points' => 1000,
                    'discount_percentage' => 12.00,
                    'badge_color' => '#10B981',
                ]);
            }

            // Update Platinum (1,600 PTS, order 5)
            DB::table('loyalty_tiers')->where('slug', 'platinum')->update([
                'order_index' => 5,
                'min_lifetime_points' => 1600,
            ]);

            // Add Ruby (2,400 PTS)
            $rubyExists = DB::table('loyalty_tiers')->where('slug', 'ruby')->exists();
            if (! $rubyExists) {
                DB::table('loyalty_tiers')->insert([
                    'name' => 'Ruby',
                    'slug' => 'ruby',
                    'min_lifetime_points' => 2400,
                    'discount_percentage' => 18.00,
                    'ticket_priority_level' => 'executive',
                    'badge_color' => '#E11D48',
                    'order_index' => 6,
                    'is_active' => true,
                    'perks_payload' => json_encode([
                        'perks' => [
                            '18% Automatic Invoice Deduction',
                            'Dedicated Senior Solutions Architect',
                            '2-Hour Guaranteed Emergency SLA',
                            'Complimentary Monthly Performance Scan',
                        ],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('loyalty_tiers')->where('slug', 'ruby')->update([
                    'order_index' => 6,
                    'min_lifetime_points' => 2400,
                    'discount_percentage' => 18.00,
                    'badge_color' => '#E11D48',
                ]);
            }

            // Update Diamond (3,500 PTS, order 7)
            DB::table('loyalty_tiers')->where('slug', 'diamond')->update([
                'order_index' => 7,
                'min_lifetime_points' => 3500,
            ]);

            // Update Obsidian (7,500 PTS, order 8)
            DB::table('loyalty_tiers')->where('slug', 'obsidian')->update([
                'order_index' => 8,
                'min_lifetime_points' => 7500,
            ]);
        }

        // Backfill / sync users lifetime points and re-evaluate tiers
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'loyalty_lifetime_points') && Schema::hasColumn('users', 'loyalty_points_balance')) {
            DB::statement('UPDATE users SET loyalty_lifetime_points = loyalty_points_balance WHERE loyalty_lifetime_points < loyalty_points_balance');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            DB::table('loyalty_tiers')->whereIn('slug', ['emerald', 'ruby'])->delete();
            DB::table('loyalty_tiers')->where('slug', 'platinum')->update(['order_index' => 4, 'min_lifetime_points' => 1500]);
            DB::table('loyalty_tiers')->where('slug', 'diamond')->update(['order_index' => 5]);
            DB::table('loyalty_tiers')->where('slug', 'obsidian')->update(['order_index' => 6]);
        }
    }
};
