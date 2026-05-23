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
        Schema::table('user_referral_request_withdraws', function (Blueprint $table) {

            $table->bigInteger('cost_transaction_id')->unsigned()->nullable();
            $table->foreign('cost_transaction_id')->references('id')->on('cost_transactions')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_referral_request_withdraws', function (Blueprint $table) {
            //
        });
    }
};
