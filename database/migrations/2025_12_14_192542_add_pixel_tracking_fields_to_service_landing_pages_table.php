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
            $table->string('facebook_pixel_id', 100)->nullable()->after('is_active');
            $table->string('tiktok_pixel_id', 100)->nullable()->after('facebook_pixel_id');
            $table->string('snapchat_pixel_id', 100)->nullable()->after('tiktok_pixel_id');
            $table->string('google_analytics_id', 100)->nullable()->after('snapchat_pixel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_landing_pages', function (Blueprint $table) {
            $table->dropColumn(['facebook_pixel_id', 'tiktok_pixel_id', 'snapchat_pixel_id', 'google_analytics_id']);
        });
    }
};
