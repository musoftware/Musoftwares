<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- loyalty_tiers ---
        if (! Schema::hasTable('loyalty_tiers')) {
            Schema::create('loyalty_tiers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('min_lifetime_points')->default(0)->index();
                $table->decimal('discount_percentage', 5, 2)->default(0.00);
                $table->string('ticket_priority_level')->default('standard');
                $table->string('badge_color')->default('#CD7F32');
                $table->unsignedSmallInteger('order_index')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });

            DB::table('loyalty_tiers')->insert([
                ['name' => 'Bronze',   'slug' => 'bronze',   'min_lifetime_points' => 0,    'discount_percentage' => 0.00, 'ticket_priority_level' => 'standard', 'badge_color' => '#CD7F32', 'order_index' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Silver',   'slug' => 'silver',   'min_lifetime_points' => 200,  'discount_percentage' => 5.00, 'ticket_priority_level' => 'medium',   'badge_color' => '#C0C0C0', 'order_index' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Gold',     'slug' => 'gold',     'min_lifetime_points' => 600,  'discount_percentage' => 10.00,'ticket_priority_level' => 'high',     'badge_color' => '#FFD700', 'order_index' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Platinum', 'slug' => 'platinum', 'min_lifetime_points' => 1500, 'discount_percentage' => 15.00,'ticket_priority_level' => 'vip',      'badge_color' => '#E5E4E2', 'order_index' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // --- loyalty_rules ---
        if (! Schema::hasTable('loyalty_rules')) {
            Schema::create('loyalty_rules', function (Blueprint $table) {
                $table->id();
                $table->string('event_type')->unique()->index();
                $table->unsignedInteger('base_points')->default(0);
                // JSON: for invoice_payment holds early_payment_multipliers array
                $table->json('conditions_payload')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });

            $earlyMultipliers = json_encode([
                'early_payment_multipliers' => [
                    ['days_early_min' => 7,  'multiplier' => 2.0],
                    ['days_early_min' => 3,  'multiplier' => 1.5],
                    ['days_early_min' => 0,  'multiplier' => 1.0],
                ],
                'overdue_multiplier' => 0.0,
                'require_portal_channel' => false,
            ]);

            DB::table('loyalty_rules')->insert([
                ['event_type' => 'invoice_payment',           'base_points' => 200, 'conditions_payload' => $earlyMultipliers,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['event_type' => 'ticket_portal_created',     'base_points' => 15,  'conditions_payload' => null,               'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['event_type' => 'ticket_resolved_positive',  'base_points' => 25,  'conditions_payload' => null,               'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['event_type' => 'user_welcome',              'base_points' => 50,  'conditions_payload' => null,               'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['event_type' => 'winback_bonus',             'base_points' => 150, 'conditions_payload' => null,               'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // --- Update loyalty_point_transactions ---
        Schema::table('loyalty_point_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('loyalty_point_transactions', 'loyalty_rule_id')) {
                $table->foreignId('loyalty_rule_id')->nullable()->constrained('loyalty_rules')->nullOnDelete()->after('user_id');
            }
            if (! Schema::hasColumn('loyalty_point_transactions', 'balance_after')) {
                $table->unsignedInteger('balance_after')->default(0)->after('points');
            }
            if (! Schema::hasColumn('loyalty_point_transactions', 'source_channel')) {
                $table->string('source_channel')->default('system')->after('balance_after');
            }
            if (! Schema::hasColumn('loyalty_point_transactions', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->unique()->after('source_channel');
            }
        });

        // --- Update users ---
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'loyalty_lifetime_points')) {
                $table->unsignedInteger('loyalty_lifetime_points')->default(0)->index()->after('loyalty_points_balance');
            }
            if (! Schema::hasColumn('users', 'loyalty_tier_id')) {
                $table->foreignId('loyalty_tier_id')->nullable()->constrained('loyalty_tiers')->nullOnDelete()->after('loyalty_lifetime_points');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'loyalty_tier_id')) {
                $table->dropForeign(['loyalty_tier_id']);
                $table->dropColumn('loyalty_tier_id');
            }
            if (Schema::hasColumn('users', 'loyalty_lifetime_points')) {
                $table->dropColumn('loyalty_lifetime_points');
            }
        });

        Schema::table('loyalty_point_transactions', function (Blueprint $table) {
            foreach (['loyalty_rule_id', 'balance_after', 'source_channel', 'idempotency_key'] as $col) {
                if (Schema::hasColumn('loyalty_point_transactions', $col)) {
                    if ($col === 'loyalty_rule_id') {
                        $table->dropForeign(['loyalty_rule_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('loyalty_tiers');
    }
};
