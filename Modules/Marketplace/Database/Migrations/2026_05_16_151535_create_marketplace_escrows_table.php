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
        if (Schema::hasTable('marketplace_escrows')) {
            return;
        }

        Schema::create('marketplace_escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();

            $table->unsignedBigInteger('buyer_wallet_transaction_id')->nullable();
            $table->unsignedBigInteger('seller_wallet_transaction_id')->nullable();

            $table->decimal('amount', 20, 8);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('business_amount', 20, 8);
            $table->foreignId('business_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 8);
            $table->date('exchange_rate_date');

            $table->enum('status', [
                'pending',
                'held',
                'released',
                'refunded',
                'disputed'
            ])->default('pending');

            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_escrows');
    }
};
