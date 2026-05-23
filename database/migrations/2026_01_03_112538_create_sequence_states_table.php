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
        Schema::create('sequence_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            
            // Morphable to User or Lead
            $table->morphs('assignable');
            
            $table->string('status')->default('active'); // active, completed, stopped, manual_stop
            $table->integer('current_step_order')->default(0);
            
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('last_whatsapp_sent_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_states');
    }
};
