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
        if (! Schema::hasTable('serial_software_resellers')) {
            Schema::create('serial_software_resellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
                $table->unsignedInteger('max_devices')->nullable(); // null = unlimited
                $table->string('status')->default('active'); // active, suspended
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'serial_software_id'], 'reseller_software_unique');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_software_resellers');
    }
};
