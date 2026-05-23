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
        Schema::table('users', function (Blueprint $table) {

            $table->double('user_balance', 33, 3)->default('0');
            $table->double('total_paid', 33, 3)->default('0');
            $table->double('withdrawn_commission', 33, 3)->default('0');
            $table->double('pending_commission', 33, 3)->default('0');
            $table->double('withdrawing_commission', 33, 3)->default('0');
            $table->double('total_cost', 33, 3)->default('0');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
