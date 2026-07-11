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
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->bigInteger('coupon_id')->unsigned()->index();
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Transaction details
            $table->bigInteger('transaction_id')->unsigned()->nullable()->index();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

            // Redemption details
            $table->decimal('discount_amount', 15, 10);
            $table->bigInteger('currency')->unsigned();
            $table->foreign('currency')->references('id')->on('currencies');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'coupon_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
