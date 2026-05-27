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
            $oldData = \Illuminate\Support\Facades\DB::table('gold_world_prices')->get();
            foreach ($oldData as $row) {
                // Best effort migration mapping
                \Illuminate\Support\Facades\DB::table('gold_price_history')->insertOrIgnore([
                    'source_id' => 1, // Default or generic source
                    'global_price_usd' => $row->price ?? 0,
                    'karat_24' => $row->karat_24 ?? 0,
                    'karat_21' => $row->karat_21 ?? 0,
                    'karat_18' => $row->karat_18 ?? 0,
                    'currency' => $row->currency ?? 'USD',
                    'recorded_at' => $row->created_at ?? now(),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
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
