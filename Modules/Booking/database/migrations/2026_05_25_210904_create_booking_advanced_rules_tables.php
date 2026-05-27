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
        Schema::create('booking_advanced_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('event_trigger')->index(); // e.g. booking.created
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_advanced_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('rule_id')->constrained('booking_advanced_rules')->cascadeOnDelete();
            $table->string('group_id')->nullable()->index(); // for AND/OR grouping
            $table->string('type'); // e.g., customer, amount, time
            $table->string('operator'); // e.g., equals, greater_than, contains
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_advanced_rule_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('rule_id')->constrained('booking_advanced_rules')->cascadeOnDelete();
            $table->string('type'); // e.g., reject, require_approval, assign_resource
            $table->json('parameters')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_advanced_rule_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('rule_id')->constrained('booking_advanced_rules')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('status')->index(); // pending, success, failed, conflict
            $table->integer('execution_time_ms')->nullable();
            $table->boolean('is_dry_run')->default(false);
            $table->timestamps();
        });

        Schema::create('booking_advanced_rule_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('execution_id')->nullable()->constrained('booking_advanced_rule_executions')->cascadeOnDelete();
            $table->string('level')->index(); // info, warning, error
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_advanced_rule_logs');
        Schema::dropIfExists('booking_advanced_rule_executions');
        Schema::dropIfExists('booking_advanced_rule_actions');
        Schema::dropIfExists('booking_advanced_rule_conditions');
        Schema::dropIfExists('booking_advanced_rules');
    }
};
