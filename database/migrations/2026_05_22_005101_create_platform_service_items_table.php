<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_service_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['module', 'tool']);       // What kind of item
            $table->string('slug')->unique();               // e.g. "erp", "whatsapp", "sms"
            $table->string('name');                         // Display name
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->string('icon')->nullable();             // Lucide icon name or URL
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_service_items');
    }
};
