<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();              // starter, professional, business_suite, custom
            $table->string('name');                         // "Starter", "Professional", etc.
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->json('included_modules')->nullable();   // ["erp","crm","booking","intelligence"]
            $table->json('included_tools')->nullable();     // ["whatsapp","sms"] or ["*"] for all
            $table->json('features')->nullable();           // Feature list for display on pricing page
            $table->boolean('is_custom')->default(false);   // True only for the "Custom" plan
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_plans');
    }
};
