<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per payment link: the exact amount that link asks for. The gateway's webhook credits only this amount,
 * checked against what the gateway says was paid, and each link is counted once. Without it, a cheap link made
 * earlier could pay a bigger amount set later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_payment_orders')) {
            return;
        }

        Schema::create('builder_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->string('order_id', 191)->unique();
            $table->string('invoice', 120);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->decimal('gateway_amount', 12, 2);
            $table->string('gateway_currency', 3);
            $table->string('transaction_id', 191)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_payment_orders');
    }
};
