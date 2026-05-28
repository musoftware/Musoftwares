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
        // Migrate gold_world_prices if it exists
        if (Schema::hasTable('gold_world_prices')) {
            // Legacy data migration skipped due to OHLCV schema incompatibility.
            // Historical charts will rebuild from new API data automatically.
            Schema::dropIfExists('gold_world_prices');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore dropped data
    }
};
