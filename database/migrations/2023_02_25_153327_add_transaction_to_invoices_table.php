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
        Schema::table('erp_invoices', function (Blueprint $table) {

            $table->bigInteger('transaction_id')->unsigned()->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

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
        Schema::table('erp_invoices', function (Blueprint $table) {
            //
        });
    }
};
