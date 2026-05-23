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

            $table->bigInteger('affiliate_earning_id')->unsigned()->index()->nullable();
            $table->foreign('affiliate_earning_id')->references('id')->on('earnings');

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
