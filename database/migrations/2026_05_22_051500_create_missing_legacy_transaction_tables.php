<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recurring_cost_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_cost_id')->nullable();
            $table->unsignedBigInteger('cost_transaction_id')->nullable();
            $table->string('unique_id');
            $table->text('reason')->default('recurring event');
            $table->timestamps();
        });

        Schema::create('recurring_income_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_income_id')->nullable();
            $table->unsignedBigInteger('income_transaction_id')->nullable();
            $table->string('unique_id');
            $table->text('reason')->default('recurring event');
            $table->timestamps();
        });

        Schema::create('recurring_salary_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_salary_id')->nullable();
            $table->unsignedBigInteger('salary_transaction_id')->nullable();
            $table->string('unique_id');
            $table->text('reason')->default('recurring event');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('currency')->default(1);
            $table->decimal('amount', 23, 3)->default(0);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('charity_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_transaction', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('transaction_id');
            $table->timestamps();
        });

        Schema::create('cost_transaction_invoice', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_transaction_id');
            $table->unsignedBigInteger('invoice_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_transaction_invoice');
        Schema::dropIfExists('invoice_transaction');
        Schema::dropIfExists('charity_transactions');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('recurring_salary_transactions');
        Schema::dropIfExists('recurring_income_transactions');
        Schema::dropIfExists('recurring_cost_transactions');
    }
};
