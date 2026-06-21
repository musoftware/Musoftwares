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
        Schema::create('invoice_item_timers', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('project_id')->unsigned()->nullable()->index();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            $table->bigInteger('invoice_item_id')->unsigned()->index();
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->onDelete('cascade');

            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();
            $table->double('amount', 23, 3);

            $table->bigInteger('currency')->unsigned()->index()->default(1);
            $table->foreign('currency')->references('id')->on('currencies');

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
        Schema::dropIfExists('invoice_item_timers');
    }
};
