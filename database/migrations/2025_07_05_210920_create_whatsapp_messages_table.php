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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('whatsapp_channels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('recipient_number');
            $table->enum('message_type', ['text', 'otp', 'media', 'template'])->default('text');
            $table->text('message_content');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('message_id')->nullable(); // WhatsApp message ID
            $table->decimal('cost_egp', 8, 2)->default(0.25); // Cost per message in EGP
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('daily_batch_id')->nullable(); // Will add foreign key constraint later
            $table->timestamps();

            $table->index(['channel_id', 'status']);
            $table->index(['user_id', 'message_type']);
            $table->index(['recipient_number']);
            $table->index(['sent_at']);
            $table->index(['transaction_id']);
            $table->index(['daily_batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
