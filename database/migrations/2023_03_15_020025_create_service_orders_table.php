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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');

            $table->double('seller_service_amount');
            $table->bigInteger('seller_currency')->unsigned()->index()->default(1);
            $table->foreign('seller_currency')->references('id')->on('currencies');

            $table->double('buyer_service_amount');
            $table->bigInteger('buyer_currency')->unsigned()->index()->default(1);
            $table->foreign('buyer_currency')->references('id')->on('currencies');

            $table->bigInteger('qty')->default(1);

            $table->enum('status', ['active', 'late', 'delivered', 'completed', 'cancelled'])->default('active');

            $table->bigInteger('seller_earning_id')->unsigned()->index()->nullable();
            $table->foreign('seller_earning_id')->references('id')->on('earnings');

            $table->bigInteger('buyer_transaction_id')->unsigned()->index()->nullable();
            $table->foreign('buyer_transaction_id')->references('id')->on('transactions');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_orders');
    }
};
