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
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('shop_orders')->onDelete('cascade');

            $table->bigInteger('product_sku_id')->nullable()->unsigned();
            $table->foreign('product_sku_id')->references('id')->on('shop_product_skus')->onDelete('cascade');

            $table->double('total', 8, 2);


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
        Schema::dropIfExists('shop_order_items');
    }
};
