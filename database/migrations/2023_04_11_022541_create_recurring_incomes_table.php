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
        Schema::create('recurring_incomes', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->double('amount', 30, 2);
            $table->bigInteger('currency')->unsigned()->index()->default(1);
            $table->foreign('currency')->references('id')->on('currencies');

            $table->text('reason');

            $table->enum('recurring', ['day', 'week', 'month', 'year']);
            $table->integer('recurring_times');
            $table->text('recurring_times_week')->nullable();
            $table->text('recurring_times_month')->nullable();
            $table->text('recurring_times_year')->nullable();

            $table->date('start_date')->nullable();
            $table->date('current_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recurring_incomes');
    }
};
