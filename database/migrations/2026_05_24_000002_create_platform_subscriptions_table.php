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
        Schema::create('platform_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('currency_id')->default(1)->constrained('currencies');
            $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('custom_items')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Assuming PlatformPlan maps to a table, but not enforcing FK to avoid constraint issues if table names differ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_subscriptions');
    }
};
