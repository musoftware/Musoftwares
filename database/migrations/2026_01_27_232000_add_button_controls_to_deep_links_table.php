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
        Schema::table('deep_links', function (Blueprint $table) {
            $table->boolean('show_open_app')->default(true);
            $table->boolean('show_copy')->default(true);
            $table->boolean('show_chrome')->default(true);
            $table->boolean('show_firefox')->default(true);
            $table->boolean('show_default_browser')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deep_links', function (Blueprint $table) {
            $table->dropColumn([
                'show_open_app',
                'show_copy',
                'show_chrome',
                'show_firefox',
                'show_default_browser'
            ]);
        });
    }
};
