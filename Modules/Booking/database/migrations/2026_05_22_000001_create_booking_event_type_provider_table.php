<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_event_type_provider', function (Blueprint $table) {
            $table->foreignId('booking_event_type_id')->constrained('booking_event_types')->cascadeOnDelete();
            $table->foreignId('booking_provider_id')->constrained('booking_providers')->cascadeOnDelete();
            $table->primary(['booking_event_type_id', 'booking_provider_id'], 'event_provider_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_event_type_provider');
    }
};
