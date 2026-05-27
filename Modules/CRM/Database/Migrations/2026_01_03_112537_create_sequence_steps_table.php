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
        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            $table->integer('delay')->default(0);
            $table->string('unit')->default('day'); // minute, hour, day
            $table->integer('order')->default(0);
            
            // Channel flags
            $table->boolean('send_email')->default(false);
            $table->boolean('send_whatsapp')->default(false);
            
            // Content
            $table->json('email_subject')->nullable(); // JSON for translation keys
            $table->json('email_content')->nullable();
            $table->json('whatsapp_content')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_steps');
    }
};
