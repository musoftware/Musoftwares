<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('type')->nullable(); // doctor, room, device, etc.
            $table->string('specialization')->nullable();
            $table->string('calendar_color')->nullable();
            $table->string('timezone')->nullable();
            $table->integer('slot_duration')->default(30); // in minutes
            $table->integer('buffer_before')->default(0);
            $table->integer('buffer_after')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_resources');
    }
};
