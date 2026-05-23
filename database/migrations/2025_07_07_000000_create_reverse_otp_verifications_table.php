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
        Schema::create('reverse_otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('whatsapp_channels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('sender_phone_number'); // Phone number that sent the OTP
            $table->string('otp_code'); // The OTP code sent by user
            $table->string('verification_id')->unique(); // Unique verification ID
            $table->enum('status', ['pending', 'verified', 'expired', 'invalid'])->default('pending');
            $table->timestamp('verified_at')->nullable();

            $table->timestamp('expires_at')->nullable(); // OTP expiration time
            $table->text('callback_url')->nullable(); // Optional callback URL for verification
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            $table->index(['channel_id', 'status']);
            $table->index(['verification_id']);
            $table->index(['sender_phone_number']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverse_otp_verifications');
    }
};
