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
        if (config('database.default') !== 'sqlite') {
            Schema::table('gold_savers', function (Blueprint $table) {
                // Modify the carat enum to include '22'
                $table->enum('carat', ['14', '18', '21', '22', '24'])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('gold_savers', function (Blueprint $table) {
                // Revert back to original enum without '22'
                $table->enum('carat', ['14', '18', '21', '24'])->change();
            });
        }
    }
};
