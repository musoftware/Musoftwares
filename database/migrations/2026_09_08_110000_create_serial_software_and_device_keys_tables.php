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
        Schema::create('serial_software_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('default_value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['serial_software_id', 'key'], 'serial_software_key_unique');
        });

        Schema::create('serial_device_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_device_id')->constrained('serial_devices')->cascadeOnDelete();
            $table->foreignId('serial_software_key_id')->constrained('serial_software_keys')->cascadeOnDelete();
            $table->text('value');
            $table->timestamps();

            $table->unique(['serial_device_id', 'serial_software_key_id'], 'serial_device_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_device_keys');
        Schema::dropIfExists('serial_software_keys');
    }
};
