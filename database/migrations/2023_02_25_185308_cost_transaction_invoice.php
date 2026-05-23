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
        Schema::create('cost_transaction_invoice', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_transaction_id')->unique();
            $table->foreign('cost_transaction_id')->references('id')->on('cost_transactions')->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id');
            $table->foreign('invoice_id')->references('id')->on('erp_invoices');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cost_transaction_invoice', function (Blueprint $table) {
            Schema::dropIfExists('cost_transaction_invoice');
        });
    }
};
