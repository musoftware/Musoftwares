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
        Schema::create('booking_wa_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type')->index(); // e.g. 'booking_confirmation', 'upcoming_reminder'
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // A tenant can only have one active template per type
            $table->unique(['tenant_id', 'type']);
        });

        Schema::create('booking_wa_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('trigger_type'); // e.g. '24_hours_before'
            $table->timestamp('scheduled_at')->index(); // When it should be sent
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('booking_wa_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('phone_number');
            $table->text('message_content');
            $table->string('provider_message_id')->nullable()->index(); // Used to map webhooks back to the log
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_wa_logs');
        Schema::dropIfExists('booking_wa_schedules');
        Schema::dropIfExists('booking_wa_templates');
    }
};
