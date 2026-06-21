<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_market_sources', function (Blueprint $table) {
            $table->id();
            // null tenant_id = global system source shared across tenants
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->string('name');
            $table->string('driver')->default('api'); // api, websocket, manual, vendor
            $table->string('market_key')->index();
            $table->string('base_currency', 3)->default('USD');
            $table->string('endpoint_url')->nullable();
            $table->text('credentials')->nullable(); // JSON encrypted

            // Failover priority (lower = higher priority)
            $table->unsignedSmallInteger('priority')->default(100);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_healthy')->default(true);

            // Provider health tracking
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedSmallInteger('failure_count')->default(0);
            $table->decimal('uptime_pct', 5, 2)->default(100.00);
            $table->unsignedInteger('avg_latency_ms')->nullable();

            // Anomaly detection threshold (% deviation allowed before flagging)
            $table->decimal('validation_threshold_pct', 5, 2)->default(15.00);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'market_key', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_market_sources');
    }
};
