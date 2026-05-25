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
        Schema::create('booking_smart_slot_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('target_metric'); // gap_reduction, load_balance, max_utilization
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_smart_slot_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->date('date')->index();
            $table->integer('fragmentation_score')->default(0); // 0-100
            $table->decimal('utilization_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('booking_smart_slot_optimizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('status'); // pending, completed, failed
            $table->json('metrics_before')->nullable();
            $table->json('metrics_after')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_smart_slot_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->dateTime('predicted_peak_hour');
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('booking_smart_slot_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('action'); // optimized, generated, rebalanced
            $table->text('description');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_smart_slot_logs');
        Schema::dropIfExists('booking_smart_slot_predictions');
        Schema::dropIfExists('booking_smart_slot_optimizations');
        Schema::dropIfExists('booking_smart_slot_snapshots');
        Schema::dropIfExists('booking_smart_slot_rules');
    }
};
