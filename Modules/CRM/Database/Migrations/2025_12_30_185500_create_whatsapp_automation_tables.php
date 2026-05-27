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
        Schema::create('whatsapp_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type')->default('on_lead_creation'); // on_lead_creation, on_user_register
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('whatsapp_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('delay')->default(0); // number of units after previous step
            $table->string('unit')->default('day'); // minute, hour, day
            
            // Content handling multilingual
            $table->json('content');
            // We removed subject as it is not needed for WhatsApp usually
            
            $table->integer('order')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // Polymorphic table to track progress for Leads OR Users
        Schema::create('whatsapp_sequence_states', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation: assignable_id, assignable_type
            $table->morphs('assignable'); 
            $table->foreignId('whatsapp_sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('current_step_order')->default(0);
            $table->timestamp('last_message_sent_at')->nullable();
            $table->string('status')->default('active'); // active, completed, stopped_by_reply, manual_stop
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sequence_states');
        Schema::dropIfExists('whatsapp_sequence_steps');
        Schema::dropIfExists('whatsapp_sequences');
    }
};
