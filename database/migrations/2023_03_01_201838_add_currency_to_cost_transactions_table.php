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
        Schema::table('cost_transactions', function (Blueprint $table) {

            $table->double('business_amount', 23, 3)->default(0);
            $table->boolean('business_calculated')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            //
        });
    }
};
