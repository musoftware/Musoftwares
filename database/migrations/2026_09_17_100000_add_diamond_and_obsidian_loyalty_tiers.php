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
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                if (! Schema::hasColumn('loyalty_tiers', 'perks_payload')) {
                    $table->json('perks_payload')->nullable()->after('badge_color');
                }
            });

            // Update Bronze
            DB::table('loyalty_tiers')->where('slug', 'bronze')->update([
                'perks_payload' => json_encode([
                    'perks' => [
                        'Instant Support Ticket Priority',
                        'Transparent Audit Ledger Tracking',
                        'Standard Queue Processing',
                    ],
                ]),
                'updated_at' => now(),
            ]);

            // Update Silver
            DB::table('loyalty_tiers')->where('slug', 'silver')->update([
                'perks_payload' => json_encode([
                    'perks' => [
                        '5% Automatic Invoice Deduction',
                        'Accelerated Ticket Dispatch SLA',
                        'Bi-Weekly Milestone Progress Audits',
                    ],
                ]),
                'updated_at' => now(),
            ]);

            // Update Gold
            DB::table('loyalty_tiers')->where('slug', 'gold')->update([
                'perks_payload' => json_encode([
                    'perks' => [
                        '10% Automatic Invoice Deduction',
                        'High-Priority Fast Track Queue',
                        'Dedicated Senior Technical Lead',
                        'Monthly Architecture Review Call',
                    ],
                ]),
                'updated_at' => now(),
            ]);

            // Update Platinum
            DB::table('loyalty_tiers')->where('slug', 'platinum')->update([
                'perks_payload' => json_encode([
                    'perks' => [
                        '15% Automatic Invoice Deduction',
                        'Executive VIP Zero-Queue SLA',
                        'Dedicated Solutions Architect',
                        'Direct Technical Emergency Hotline',
                    ],
                ]),
                'updated_at' => now(),
            ]);

            // Add Diamond (3,500 PTS)
            $diamondExists = DB::table('loyalty_tiers')->where('slug', 'diamond')->exists();
            if (! $diamondExists) {
                DB::table('loyalty_tiers')->insert([
                    'name' => 'Diamond',
                    'slug' => 'diamond',
                    'min_lifetime_points' => 3500,
                    'discount_percentage' => 20.00,
                    'ticket_priority_level' => 'executive',
                    'badge_color' => '#00F0FF',
                    'order_index' => 5,
                    'is_active' => true,
                    'perks_payload' => json_encode([
                        'perks' => [
                            '20% Automatic Invoice Deduction',
                            '15-Min Guaranteed Engineering SLA',
                            'Comprehensive Code & Cloud Audits',
                            'Priority Feature Request Fast-Track',
                        ],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Add Obsidian (7,500 PTS)
            $obsidianExists = DB::table('loyalty_tiers')->where('slug', 'obsidian')->exists();
            if (! $obsidianExists) {
                DB::table('loyalty_tiers')->insert([
                    'name' => 'Obsidian',
                    'slug' => 'obsidian',
                    'min_lifetime_points' => 7500,
                    'discount_percentage' => 25.00,
                    'ticket_priority_level' => 'immediate',
                    'badge_color' => '#7C3AED',
                    'order_index' => 6,
                    'is_active' => true,
                    'perks_payload' => json_encode([
                        'perks' => [
                            '25% Maximum Invoice Deduction',
                            'Direct 24/7 Line to CTO & Leadership',
                            'Custom Enterprise Architecture',
                            'Dedicated Sprint Engineering Squad',
                        ],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            DB::table('loyalty_tiers')->whereIn('slug', ['diamond', 'obsidian'])->delete();

            if (Schema::hasColumn('loyalty_tiers', 'perks_payload')) {
                Schema::table('loyalty_tiers', function (Blueprint $table) {
                    $table->dropColumn('perks_payload');
                });
            }
        }
    }
};
