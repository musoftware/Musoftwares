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
        Schema::create('booking_page_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('slug')->unique();
            $table->string('title')->default('Book an Appointment');
            $table->text('description')->nullable();
            $table->string('primary_color')->default('#000000');
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Typically: $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_page_settings');
    }
};
