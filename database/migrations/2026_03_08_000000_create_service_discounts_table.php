<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Service owner can create discounts: date range, Hijri dates, "valid X days from first view", or new users only.
     * Discount can reduce price until a minimum (e.g. Ramadan sale).
     */
    public function up(): void
    {
        Schema::create('service_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('name')->nullable()->comment('e.g. Ramadan 2026');
            $table->string('type', 32)->default('date_range')->comment('date_range|hijri_range|days_from_now|new_users_only');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('hijri_start', 20)->nullable()->comment('e.g. 1446-09-01');
            $table->string('hijri_end', 20)->nullable();
            $table->unsignedSmallInteger('days_from_now')->nullable()->comment('Valid X days from first visit or from now');
            $table->boolean('new_users_only')->default(false);
            $table->string('discount_type', 16)->default('percent')->comment('percent|fixed');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('min_price_until', 10, 2)->nullable()->comment('Do not reduce price below this (seller floor)');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_discounts');
    }
};
