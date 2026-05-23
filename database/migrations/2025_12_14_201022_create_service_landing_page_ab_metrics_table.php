<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tracks A/B testing metrics for landing pages and their variants
     */
    public function up(): void
    {
        Schema::create('service_landing_page_ab_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landing_page_id');
            $table->foreign('landing_page_id')->references('id')->on('service_landing_pages')->onDelete('cascade');
            
            // Session tracking
            $table->string('session_id')->index();
            $table->string('visitor_ip', 45)->nullable(); // IPv6 support
            $table->text('user_agent')->nullable();
            
            // Device & location info
            $table->boolean('is_mobile')->default(false)->index();
            $table->boolean('is_tablet')->default(false);
            $table->string('device_type', 20)->nullable(); // mobile, tablet, desktop
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country', 2)->nullable()->index(); // ISO 2-letter code
            $table->string('city')->nullable();
            $table->string('language', 10)->nullable();
            
            // Traffic source
            $table->string('referrer_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            
            // Interaction metrics
            $table->integer('page_views')->default(1);
            $table->integer('unique_views')->default(1);
            $table->integer('cta_clicks')->default(0);
            $table->integer('form_views')->default(0);
            $table->integer('form_submissions')->default(0);
            $table->integer('scroll_depth_percentage')->default(0); // Max scroll depth
            $table->integer('time_on_page_seconds')->default(0);
            
            // Conversion tracking
            $table->boolean('converted')->default(false)->index();
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_value', 10, 2)->nullable(); // If applicable
            
            // Timestamps
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['landing_page_id', 'converted'], 'slp_metrics_page_converted');
            $table->index(['landing_page_id', 'created_at'], 'slp_metrics_page_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_landing_page_ab_metrics');
    }
};
