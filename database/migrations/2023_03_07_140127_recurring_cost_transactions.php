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
            $table->foreign('recurring_cost_id')->references('id')->on('recurring_costs')->cascadeOnDelete();

            $table->unsignedBigInteger('cost_transaction_id')->nullable();
            $table->foreign('cost_transaction_id')->references('id')->on('cost_transactions')->nullOnDelete();

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
        Schema::dropIfExists('recurring_cost_transactions');
    }
};
