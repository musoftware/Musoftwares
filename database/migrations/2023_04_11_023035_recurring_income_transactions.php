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
        Schema::create('recurring_income_transactions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('recurring_income_id')->nullable();
            $table->foreign('recurring_income_id')->references('id')->on('recurring_incomes')->cascadeOnDelete();

            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();

            $table->string('unique_id', 255)->unique();

            $table->string('reason', 255)->default('recurring event');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recurring_income_transactions');
    }
};
