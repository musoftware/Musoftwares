<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per payment event a provider (e.g. Kashier) has delivered and we have applied.
 * The unique (provider, external_id) pair is the idempotency guard: a second delivery of the
 * same event fails the insert, so money is never credited twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_provider_events')) {
            return;
        }

        Schema::create('payment_provider_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('external_id', 191);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_events');
    }
};
