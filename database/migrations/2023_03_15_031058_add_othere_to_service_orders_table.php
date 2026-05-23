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
        Schema::table('service_orders', function (Blueprint $table) {

            $table->bigInteger('affiliate_user_id')->unsigned()->nullable();
            $table->foreign('affiliate_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->double('affiliate_service_amount')->nullable();
            $table->bigInteger('affiliate_currency')->unsigned()->index()->nullable();
            $table->foreign('affiliate_currency')->references('id')->on('currencies');

            $table->dateTime('completed_at')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_orders', function (Blueprint $table) {
            //
        });
    }
};
