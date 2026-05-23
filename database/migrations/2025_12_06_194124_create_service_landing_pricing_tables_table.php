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
        Schema::create('service_landing_pricing_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landing_page_id');
            $table->foreign('landing_page_id')->references('id')->on('service_landing_pages')->onDelete('cascade');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('period')->nullable(); // e.g., "per month", "one-time"
            $table->json('features')->nullable(); // Array of feature strings
            $table->boolean('is_popular')->default(false);
            $table->string('cta_text')->default('Get Started');
            $table->string('cta_link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_landing_pricing_tables');
    }
};
