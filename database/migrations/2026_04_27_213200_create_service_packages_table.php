<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('service_id')->constrained()->onDelete('cascade');
            $blueprint->string('name'); // Basic, Standard, Premium
            $blueprint->string('title')->nullable();
            $blueprint->text('description')->nullable();
            $blueprint->decimal('price', 15, 2);
            $blueprint->integer('delivery_days')->default(1);
            $blueprint->integer('revisions')->default(0); // -1 for unlimited
            $blueprint->json('features')->nullable(); // List of features included
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
