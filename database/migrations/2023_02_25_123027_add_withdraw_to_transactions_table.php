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
        Schema::table('transactions', function (Blueprint $table) {

            //            $table->bigInteger('referred_user_id')->unsigned()->nullable();
            //            $table->foreign('referred_user_id')->references('id')->on('users')->onDelete('cascade');
            //
            //            $table->bigInteger('referred_invoice_id')->unsigned()->nullable();
            //            $table->foreign('referred_invoice_id')->references('id')->on('invoices')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
