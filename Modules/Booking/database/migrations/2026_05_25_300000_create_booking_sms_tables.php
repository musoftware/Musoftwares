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
        Schema::create('booking_sms_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('provider_name')->default('twilio'); // twilio, smsmisr
            $table->text('provider_credentials')->nullable(); // encrypted JSON (username, password, api_key)
            $table->string('sender_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type'); // confirmation, reminder_24h, cancellation
            $table->text('content'); // Contains {{customer_name}}, {{booking_time}}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index()->nullable();
            $table->string('provider');
            $table->string('mobile');
            $table->text('content');
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_sms_logs');
        Schema::dropIfExists('booking_sms_templates');
        Schema::dropIfExists('booking_sms_settings');
    }
};
