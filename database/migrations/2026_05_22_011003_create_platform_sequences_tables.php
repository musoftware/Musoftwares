<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type')->default('manual');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('platform_sequences')->cascadeOnDelete();
            
            $table->integer('order')->default(0);
            $table->integer('delay')->default(0); // number
            $table->string('unit')->default('day'); // minute, hour, day
            
            $table->boolean('send_email')->default(false);
            $table->boolean('send_whatsapp')->default(false);
            
            $table->json('email_subject')->nullable(); // {en: '', ar: ''}
            $table->json('email_content')->nullable();
            $table->json('whatsapp_content')->nullable();
            
            $table->timestamps();
        });

        Schema::create('platform_sequence_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('platform_sequences')->cascadeOnDelete();
            
            // Polymorphic relation to allow Leads or Users
            $table->morphs('assignable');
            
            $table->string('status')->default('active'); // active, completed, paused, failed
            $table->integer('current_step_order')->default(0);
            
            $table->timestamp('next_action_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_sequence_states');
        Schema::dropIfExists('platform_sequence_steps');
        Schema::dropIfExists('platform_sequences');
    }
};
