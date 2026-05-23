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
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('whatsapp_channels')->onDelete('cascade');
            $table->string('phone_number')->nullable();
            $table->string('name')->nullable();
            $table->string('push_name')->nullable();
            $table->string('verified_name')->nullable();
            $table->json('contact_data')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'phone_number']);
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
