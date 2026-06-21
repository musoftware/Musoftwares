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
        Schema::create('booking_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_event_type_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('weekday'); // 0-6 for Sunday-Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_availability_rules');
    }
};
