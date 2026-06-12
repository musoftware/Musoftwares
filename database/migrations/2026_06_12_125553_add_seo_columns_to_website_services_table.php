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
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_ar')->nullable();
            $table->string('seo_description_en', 500)->nullable();
            $table->string('seo_description_ar', 500)->nullable();
            $table->string('seo_keywords_en', 500)->nullable();
            $table->string('seo_keywords_ar', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            $table->dropColumn([
                'seo_title_en', 'seo_title_ar',
                'seo_description_en', 'seo_description_ar',
                'seo_keywords_en', 'seo_keywords_ar'
            ]);
        });
    }
};
