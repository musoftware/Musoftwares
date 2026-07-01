<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_services', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (! Schema::hasColumn('marketplace_services', 'faq')) {
                $table->json('faq')->nullable()->after('tags');
            }
            if (! Schema::hasColumn('marketplace_services', 'requirements')) {
                $table->json('requirements')->nullable()->after('faq');
            }
            if (! Schema::hasColumn('marketplace_services', 'gallery')) {
                $table->json('gallery')->nullable()->after('requirements');
            }
            if (! Schema::hasColumn('marketplace_services', 'video_url')) {
                $table->string('video_url')->nullable()->after('gallery');
            }
        });

        Schema::table('marketplace_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_packages', 'revisions')) {
                $table->integer('revisions')->default(2)->after('delivery_days');
            }
            if (! Schema::hasColumn('marketplace_packages', 'features')) {
                $table->json('features')->nullable()->after('revisions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $columns = ['tags', 'faq', 'requirements', 'gallery', 'video_url'];
            $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn('marketplace_services', $c)));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('marketplace_packages', function (Blueprint $table) {
            $columns = ['revisions', 'features'];
            $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn('marketplace_packages', $c)));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};