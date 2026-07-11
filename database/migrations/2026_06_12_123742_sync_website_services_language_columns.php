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
            if (Schema::hasColumn('website_services', 'title')) {
                $table->renameColumn('title', 'title_en');
            }
            if (Schema::hasColumn('website_services', 'subtitle')) {
                $table->renameColumn('subtitle', 'subtitle_en');
            }
            if (Schema::hasColumn('website_services', 'description')) {
                $table->renameColumn('description', 'description_en');
            }
        });

        Schema::table('website_services', function (Blueprint $table) {
            if (! Schema::hasColumn('website_services', 'title_ar')) {
                $table->string('title_ar')->nullable()->after('title_en');
            }
            if (! Schema::hasColumn('website_services', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('title_ar');
            }
            if (! Schema::hasColumn('website_services', 'subtitle_ar')) {
                $table->string('subtitle_ar')->nullable()->after('subtitle_en');
            }
            if (! Schema::hasColumn('website_services', 'description_ar')) {
                $table->text('description_ar')->nullable()->after('description_en');
            }
        });
    }

    public function down(): void
    {
        // Fallback or rollback logic is omitted for safety on production data
    }
};
