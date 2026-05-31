<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateAndDropLegacyGoldTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            Schema::dropIfExists('gold_prices');
            Schema::dropIfExists('gold_world_prices');
            return;
        }

        $ignoreStr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? 'OR IGNORE' : 'IGNORE';
        $karats = ['24' => 'price_24k', '22' => 'price_22k', '21' => 'price_21k', '18' => 'price_18k', '14' => 'price_14k'];

        if (Schema::hasTable('gold_prices')) {
            if (Schema::hasColumn('gold_prices', 'price_24k')) {
                foreach ($karats as $k => $col) {
                    \Illuminate\Support\Facades\DB::statement('
                        INSERT ' . $ignoreStr . ' INTO gold_price_history 
                        (tenant_id, market_key, `interval`, karat, open_price, high_price, low_price, close_price, avg_price, tick_count, currency_id, period_start, period_end, created_at, updated_at)
                        SELECT 1, \'local_egp\', \'day\', ' . $k . ', ' . $col . ', ' . $col . ', ' . $col . ', ' . $col . ', ' . $col . ', 0, (SELECT id FROM currencies WHERE currency = \'EGP\' LIMIT 1), DATE(price_date), CONCAT(DATE(price_date), \' 23:59:59\'), NOW(), NOW()
                        FROM gold_prices WHERE ' . $col . ' > 0
                    ');
                }
                // Populate Gold Live Prices with the latest record
                \Illuminate\Support\Facades\DB::statement('
                    INSERT INTO gold_live_prices (tenant_id, market_key, currency_id, price_gram_24k, price_gram_21k, price_gram_18k, price_gram_14k, fetched_at, created_at, updated_at)
                    SELECT 1, \'local_egp\', (SELECT id FROM currencies WHERE currency = \'EGP\' LIMIT 1), price_24k, price_21k, price_18k, price_14k, price_date, NOW(), NOW()
                    FROM gold_prices ORDER BY price_date DESC LIMIT 1
                ');
            }
            Schema::dropIfExists('gold_prices');
        }

        if (Schema::hasTable('gold_world_prices')) {
            if (Schema::hasColumn('gold_world_prices', 'price_24k')) {
                foreach ($karats as $k => $col) {
                    \Illuminate\Support\Facades\DB::statement('
                        INSERT ' . $ignoreStr . ' INTO gold_price_history 
                        (tenant_id, market_key, `interval`, karat, open_price, high_price, low_price, close_price, avg_price, tick_count, currency_id, period_start, period_end, created_at, updated_at)
                        SELECT 1, \'global_usd\', \'day\', ' . $k . ', ' . $col . ', ' . $col . ', ' . $col . ', ' . $col . ', ' . $col . ', 0, (SELECT id FROM currencies WHERE currency = \'USD\' LIMIT 1), DATE(price_date), CONCAT(DATE(price_date), \' 23:59:59\'), NOW(), NOW()
                        FROM gold_world_prices WHERE ' . $col . ' > 0
                    ');
                }
                // Populate Gold Live Prices with the latest record
                \Illuminate\Support\Facades\DB::statement('
                    INSERT INTO gold_live_prices (tenant_id, market_key, currency_id, price_gram_24k, price_gram_21k, price_gram_18k, price_gram_14k, fetched_at, created_at, updated_at)
                    SELECT 1, \'global_usd\', (SELECT id FROM currencies WHERE currency = \'USD\' LIMIT 1), price_24k, price_21k, price_18k, price_14k, price_date, NOW(), NOW()
                    FROM gold_world_prices ORDER BY price_date DESC LIMIT 1
                ');
            }
            Schema::dropIfExists('gold_world_prices');
        }
    }

    public function down(): void
    {
        // Cannot restore dropped data
    }
}
