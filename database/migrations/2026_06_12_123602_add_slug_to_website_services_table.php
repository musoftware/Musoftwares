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
            if (!Schema::hasColumn('website_services', 'slug')) {
                $table->string('slug')->unique()->after('title_ar')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            if (Schema::hasColumn('website_services', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
