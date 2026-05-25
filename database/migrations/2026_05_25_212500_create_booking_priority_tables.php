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
        Schema::create('booking_priority_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('code')->index(); // normal, high, vip, emergency, enterprise
            $table->string('name');
            $table->integer('weight')->default(0)->index(); // Higher weight = higher priority
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_priority_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->morphs('model'); // e.g., Customer or Booking
            $table->foreignId('priority_level_id')->constrained('booking_priority_levels')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable(); // user_id
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_priority_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->json('conditions')->nullable(); // rules to match
            $table->foreignId('priority_level_id')->constrained('booking_priority_levels')->cascadeOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('booking_priority_queue_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('type'); // escalated, bypassed
            $table->integer('previous_position')->nullable();
            $table->integer('new_position')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_priority_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('action'); // assigned, escalated, queue_jump
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
        Schema::dropIfExists('booking_priority_logs');
        Schema::dropIfExists('booking_priority_queue_events');
        Schema::dropIfExists('booking_priority_rules');
        Schema::dropIfExists('booking_priority_assignments');
        Schema::dropIfExists('booking_priority_levels');
    }
};
