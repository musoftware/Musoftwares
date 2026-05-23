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
        Schema::table('service_landing_pages', function (Blueprint $table) {
            // Basic SEO Meta Tags
            $table->string('meta_title')->nullable()->after('template');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');

            // Open Graph Tags
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');

            // Twitter Card Tags
            $table->enum('twitter_card_type', ['summary', 'summary_large_image', 'app', 'player'])->default('summary_large_image')->after('og_image');
            $table->string('twitter_title')->nullable()->after('twitter_card_type');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image')->nullable()->after('twitter_description');

            // Additional SEO
            $table->string('canonical_url')->nullable()->after('twitter_image');
            $table->string('robots')->nullable()->after('canonical_url')->default('index, follow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'twitter_card_type',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'canonical_url',
                'robots',
            ]);
        });
    }
};
