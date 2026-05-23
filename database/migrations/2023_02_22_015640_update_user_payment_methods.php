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
        Schema::table('user_payment_methods', function (Blueprint $table) {

            $table->bigInteger('currency')->unsigned()->index();
            $table->foreign('currency')->references('id')->on('currencies')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_payment_methods', function (Blueprint $table) {
            //
        });
    }
};
