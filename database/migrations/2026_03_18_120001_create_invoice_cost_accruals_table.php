<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_cost_accruals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->double('amount', 23, 3);
            $table->unsignedBigInteger('currency');
            $table->foreign('currency')->references('id')->on('currencies')->onDelete('cascade');
            $table->enum('status', ['pending', 'credited', 'settled'])->default('pending');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->unsignedBigInteger('user_referral_request_withdraw_id')->nullable();
            $table->foreign('user_referral_request_withdraw_id', 'ica_withdraw_fk')
                ->references('id')->on('user_referral_request_withdraws')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_cost_accruals');
    }
};
