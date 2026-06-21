<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('market_key')->index();

            // Prices
            $table->decimal('price_usd_oz', 14, 4)->nullable();
            $table->decimal('price_gram_24k', 14, 4)->nullable();
            $table->decimal('price_gram_21k', 14, 4)->nullable();
            $table->decimal('price_gram_18k', 14, 4)->nullable();
            $table->decimal('price_gram_14k', 14, 4)->nullable();
            $table->decimal('buy_price', 14, 4)->nullable();
            $table->decimal('sell_price', 14, 4)->nullable();
            $table->foreignId('currency_id')->default(1)->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 14, 6)->default(1.000000);

            // Validation state
            $table->boolean('validation_passed')->default(true);
            $table->boolean('anomaly_detected')->default(false);
            $table->string('anomaly_reason')->nullable();

            // Full provider response for audit / replay
            $table->json('raw_payload')->nullable();

            // Interval classification (set when aggregating)
            $table->enum('interval', ['minute', 'hour', 'day', 'week', 'month'])->default('minute');

            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'market_key', 'fetched_at']);
            $table->index(['tenant_id', 'market_key', 'interval']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_price_snapshots');
    }
};
