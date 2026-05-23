<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Manages multiple CTA (Call-to-Action) variants for A/B testing different CTA copy/styles
     */
    public function up(): void
    {
        Schema::create('service_landing_page_cta_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landing_page_id');
            $table->foreign('landing_page_id')->references('id')->on('service_landing_pages')->onDelete('cascade');
            
            // CTA Content
            $table->string('cta_text');
            $table->string('cta_link', 500)->nullable();
            $table->text('cta_description')->nullable(); // Optional description/subtext
            $table->string('cta_icon')->nullable(); // Icon class or image URL
            
            // Styling
            $table->string('cta_style')->default('primary'); // primary, secondary, success, info, warning, danger
            $table->string('cta_size')->default('medium'); // small, medium, large
            $table->string('cta_animation')->nullable(); // pulse, bounce, shake, etc.
            
            // Position & Display
            $table->string('position')->default('hero'); // hero, sticky, exit_intent, time_based, inline
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0); // Higher priority shows first
            
            // Conditional Display Rules
            $table->boolean('show_on_first_visit')->default(true);
            $table->boolean('show_on_returning_visit')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('show_on_tablet')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            
            // Advanced targeting
            $table->json('show_in_countries')->nullable(); // ['US', 'CA', 'UK']
            $table->json('show_in_languages')->nullable(); // ['en', 'es', 'fr']
            $table->json('hide_in_countries')->nullable(); // Countries to exclude
            $table->json('hide_in_languages')->nullable(); // Languages to exclude
            $table->json('show_for_utm_sources')->nullable(); // Show only for specific traffic sources
            
            // Time-based display
            $table->integer('show_after_seconds')->nullable(); // Delay before showing
            $table->integer('show_after_scroll_percentage')->nullable(); // Show after X% scroll
            $table->boolean('show_on_exit_intent')->default(false);
            
            // A/B Testing
            $table->integer('traffic_percentage')->default(100); // What % of visitors see this
            $table->integer('impressions')->default(0); // How many times shown
            $table->integer('clicks')->default(0); // How many times clicked
            $table->decimal('click_through_rate', 5, 2)->default(0); // CTR percentage
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['landing_page_id', 'is_active'], 'slp_cta_page_active');
            $table->index(['landing_page_id', 'position'], 'slp_cta_page_position');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_landing_page_cta_variants');
    }
};
