<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── User Tool Subscriptions ──────────────────────────────────────────
        Schema::create('tool_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool_id')->index(); // References tool config GUID
            $table->string('tool_pricing_plan_id')->index(); // References plan GUID
            $table->string('billing_cycle')->default('monthly'); // monthly|yearly
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->foreignId('currency_id')->default(1)->constrained('currencies');
            $table->string('status')->default('active');   // active|cancelled|expired|suspended
            $table->string('payment_method')->nullable();  // wallet|kashier
            $table->string('payment_reference')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'tool_id']);
        });

        // ─── License Keys ─────────────────────────────────────────────────────
        Schema::create('tool_licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('license_key')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tool_id')->nullable();
            $table->foreignId('tool_subscription_id')->nullable()->constrained('tool_subscriptions')->nullOnDelete();
            $table->unsignedTinyInteger('max_devices')->default(3);
            $table->string('status')->default('active');   // active|suspended|revoked
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_licenses');
        Schema::dropIfExists('tool_subscriptions');
    }
};
