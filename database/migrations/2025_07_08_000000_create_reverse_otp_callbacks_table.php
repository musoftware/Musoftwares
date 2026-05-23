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
        if (!Schema::hasTable('reverse_otp_callbacks')) {
            Schema::create('reverse_otp_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_id')->constrained('reverse_otp_verifications')->onDelete('cascade');
            $table->string('callback_url');
            $table->json('sent_data'); // The data that was sent in the callback
            $table->integer('response_status')->nullable(); // HTTP response status code
            $table->text('response_body')->nullable(); // Response body from the callback
            $table->text('error_message')->nullable(); // Error message if callback failed
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('sent_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['verification_id']);
            $table->index(['status']);
            $table->index(['sent_at']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverse_otp_callbacks');
    }
};
