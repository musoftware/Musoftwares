<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('gold_live_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('market_key')->index(); // e.g. egypt_local, global_xau

            // Karat prices (per gram, local currency)
            $table->decimal('price_usd_oz', 14, 4)->nullable();
            $table->decimal('price_gram_24k', 14, 4)->nullable();
            $table->decimal('price_gram_21k', 14, 4)->nullable();
            $table->decimal('price_gram_18k', 14, 4)->nullable();
            $table->decimal('price_gram_14k', 14, 4)->nullable();
            $table->decimal('price_gram_custom', 14, 4)->nullable();
            $table->decimal('custom_purity', 5, 4)->nullable(); // e.g. 0.9999

            // Buy / Sell / Spread
            $table->decimal('buy_price', 14, 4)->nullable();
            $table->decimal('sell_price', 14, 4)->nullable();
            $table->decimal('spread', 14, 4)->nullable();

            // Currency & Exchange
            $table->foreignId('currency_id')->default(1)->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 14, 6)->default(1.000000);

            // Price movement
            $table->decimal('price_delta', 14, 4)->default(0);
            $table->decimal('price_delta_pct', 8, 4)->default(0);
            $table->enum('direction', ['up', 'down', 'flat'])->default('flat');

            // Provider metadata
            $table->unsignedInteger('provider_latency_ms')->nullable();

            // Staleness tracking
            $table->boolean('is_stale')->default(false);
            $table->timestamp('stale_since')->nullable();

            // Timestamps
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('broadcasted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one live row per tenant per market
            $table->unique(['tenant_id', 'market_key']);
            $table->foreign('source_id')
                  ->references('id')
                  ->on('gold_market_sources')
                  ->nullOnDelete();
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_live_prices');
    }
};
