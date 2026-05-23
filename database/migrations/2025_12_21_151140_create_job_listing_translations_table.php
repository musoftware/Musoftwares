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
        Schema::create('job_listing_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->onDelete('cascade');
            $table->string('locale', 5); // 'en' or 'ar'
            $table->string('field', 50); // 'title' or 'description'
            $table->longText('value'); // Translated content
            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['job_listing_id', 'locale', 'field']);
            $table->unique(['job_listing_id', 'locale', 'field'], 'job_listing_translations_unique'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listing_translations');
    }
};
