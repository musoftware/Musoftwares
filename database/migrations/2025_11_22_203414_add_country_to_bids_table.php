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
        if (! Schema::hasTable('bids')) {
            return;
        }
        Schema::table('bids', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('phone'); // ISO 3166-1 alpha-2 country code
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
