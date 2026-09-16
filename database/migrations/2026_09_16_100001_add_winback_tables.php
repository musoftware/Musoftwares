<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- winback_stages ---
        if (! Schema::hasTable('winback_stages')) {
            Schema::create('winback_stages', function (Blueprint $table) {
                $table->id();
                $table->string('stage_slug')->unique()->index();
                $table->unsignedSmallInteger('days_inactive');
                $table->string('channel')->default('email');
                // Optional bonus rule to activate on this stage
                $table->foreignId('incentive_rule_id')->nullable()->constrained('loyalty_rules')->nullOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('order_index')->default(0);
                $table->timestamps();
            });

            $winbackBonusRuleId = DB::table('loyalty_rules')->where('event_type', 'winback_bonus')->value('id');

            DB::table('winback_stages')->insert([
                ['stage_slug' => 'gentle_reminder',   'days_inactive' => 30,  'channel' => 'email', 'incentive_rule_id' => null,               'is_active' => true, 'order_index' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['stage_slug' => 'value_reminder',    'days_inactive' => 60,  'channel' => 'email', 'incentive_rule_id' => null,               'is_active' => true, 'order_index' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['stage_slug' => 'urgency_incentive', 'days_inactive' => 90,  'channel' => 'email', 'incentive_rule_id' => $winbackBonusRuleId, 'is_active' => true, 'order_index' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['stage_slug' => 'direct_check',      'days_inactive' => 120, 'channel' => 'email', 'incentive_rule_id' => null,               'is_active' => true, 'order_index' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // --- winback_engagements ---
        if (! Schema::hasTable('winback_engagements')) {
            Schema::create('winback_engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stage_id')->constrained('winback_stages')->cascadeOnDelete();
                $table->string('status')->default('sent')->index();
                $table->string('idempotency_key')->unique()->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('recovered_at')->nullable();
                $table->string('recovery_event_type')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'stage_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // --- loyalty_notification_logs ---
        if (! Schema::hasTable('loyalty_notification_logs')) {
            Schema::create('loyalty_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('notification_type')->index();
                $table->string('deduplication_fingerprint')->unique()->index();
                $table->string('status')->default('sent')->index();
                $table->timestamp('sent_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // --- Update users with winback fields ---
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_activity_type')) {
                $table->string('last_activity_type')->nullable()->after('last_activity_at');
            }
            if (! Schema::hasColumn('users', 'winback_unsubscribed_at')) {
                $table->timestamp('winback_unsubscribed_at')->nullable()->after('last_activity_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['last_activity_type', 'winback_unsubscribed_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('loyalty_notification_logs');
        Schema::dropIfExists('winback_engagements');
        Schema::dropIfExists('winback_stages');
    }
};
