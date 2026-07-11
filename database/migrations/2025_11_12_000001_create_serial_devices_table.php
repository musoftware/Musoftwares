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
        Schema::create('serial_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
            $table->string('device_id');
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['serial_software_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_devices');
    }
};
