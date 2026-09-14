<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tier')) {
                $table->string('tier')->default('standard')->index()->after('email');
            }
            if (! Schema::hasColumn('users', 'lifetime_spend')) {
                $table->decimal('lifetime_spend', 12, 2)->default(0.00)->index()->after('tier');
            }
            if (! Schema::hasColumn('users', 'profile_completion_percentage')) {
                $table->unsignedSmallInteger('profile_completion_percentage')->default(25)->after('lifetime_spend');
            }
            if (! Schema::hasColumn('users', 'loyalty_points_balance')) {
                $table->unsignedInteger('loyalty_points_balance')->default(0)->index()->after('profile_completion_percentage');
            }
        });

        if (! Schema::hasTable('loyalty_point_transactions')) {
            Schema::create('loyalty_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('event_type')->index();
                $table->integer('points');
                $table->nullableMorphs('reference');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'event_type']);
            });
        }

        if (! Schema::hasTable('loyalty_rewards')) {
            Schema::create('loyalty_rewards', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('reward_type'); // invoice_discount, free_maintenance_hours, license_extension
                $table->unsignedInteger('points_cost');
                $table->decimal('discount_value', 10, 2)->default(0.00);
                $table->string('discount_type')->default('fixed'); // fixed, percentage
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('loyalty_redemptions')) {
            Schema::create('loyalty_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('loyalty_reward_id')->constrained('loyalty_rewards');
                $table->unsignedInteger('points_spent');
                $table->string('status')->default('completed')->index();
                $table->nullableMorphs('applied_to');
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_point_transactions');

        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['tier', 'lifetime_spend', 'profile_completion_percentage', 'loyalty_points_balance'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
