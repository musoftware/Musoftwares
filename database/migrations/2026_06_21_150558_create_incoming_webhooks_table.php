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
        Schema::create('incoming_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // e.g., kashier, whatsapp, stripe
            $table->string('event_type')->nullable(); // e.g., payment.success, message.received
            $table->json('payload');
            $table->json('headers');
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_webhooks');
    }
};
