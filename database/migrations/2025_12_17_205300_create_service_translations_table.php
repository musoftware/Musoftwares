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
        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('locale', 5); // 'en' or 'ar'
            $table->string('field', 50); // 'title' or 'description'
            $table->longText('value'); // Translated content
            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['service_id', 'locale', 'field']);
            $table->unique(['service_id', 'locale', 'field']); // One translation per field per locale
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_translations');
    }
};
