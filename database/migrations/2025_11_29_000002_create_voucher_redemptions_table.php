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
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->bigInteger('voucher_id')->unsigned()->index();
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Transaction details
            $table->bigInteger('transaction_id')->unsigned()->nullable()->index();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

            // Spending and reward details
            $table->decimal('spent_amount', 15, 10);
            $table->bigInteger('spent_currency')->unsigned();
            $table->foreign('spent_currency')->references('id')->on('currencies');

            $table->decimal('reward_amount', 15, 10);
            $table->bigInteger('reward_currency')->unsigned();
            $table->foreign('reward_currency')->references('id')->on('currencies');

            // Reward transaction
            $table->bigInteger('reward_transaction_id')->unsigned()->nullable()->index();
            $table->foreign('reward_transaction_id')->references('id')->on('transactions')->onDelete('set null');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'voucher_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
