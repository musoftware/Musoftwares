<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_price_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();

            $table->enum('event_type', [
                'price_updated',
                'anomaly_detected',
                'provider_failed',
                'snapshot_generated',
                'stale_detected',
                'broadcast_sent',
                'provider_recovered',
                'manual_override',
            ]);

            $table->json('event_data')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'event_type', 'created_at']);
            $table->index(['tenant_id', 'severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_price_events');
    }
};
