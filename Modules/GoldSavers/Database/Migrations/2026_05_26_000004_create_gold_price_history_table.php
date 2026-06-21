<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('market_key')->index();
            $table->enum('interval', ['minute', 'hour', 'day', 'week', 'month']);
            $table->tinyInteger('karat')->default(24); // 24, 21, 18, 14

            // OHLCV-style candle data
            $table->decimal('open_price', 14, 4)->nullable();
            $table->decimal('high_price', 14, 4)->nullable();
            $table->decimal('low_price', 14, 4)->nullable();
            $table->decimal('close_price', 14, 4)->nullable();
            $table->decimal('avg_price', 14, 4)->nullable();
            $table->unsignedInteger('tick_count')->default(0);

            $table->foreignId('currency_id')->default(1)->constrained('currencies')->onDelete('restrict');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One candle per tenant+market+interval+karat+period
            $table->unique(
                ['tenant_id', 'market_key', 'interval', 'karat', 'period_start'],
                'gph_tenant_market_interval_karat_period_unique'
            );
            $table->index(
                ['tenant_id', 'market_key', 'interval', 'period_start'],
                'gph_tenant_market_interval_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_price_history');
    }
};
