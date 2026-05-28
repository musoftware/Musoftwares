<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $table->json('tags')->nullable();
            $table->json('faq')->nullable()->after('tags');
            $table->json('requirements')->nullable()->after('faq');
            $table->json('gallery')->nullable()->after('requirements');
            $table->string('video_url')->nullable()->after('gallery');
        });

        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->integer('revisions')->default(2)->after('delivery_days'); // -1 = unlimited
            $table->json('features')->nullable()->after('revisions');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $table->dropColumn(['tags', 'faq', 'requirements', 'gallery', 'video_url']);
        });

        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->dropColumn(['revisions', 'features']);
        });
    }
};
