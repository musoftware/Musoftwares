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
        Schema::create('shop_sku_values', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_sku_value_id');

            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');

            $table->bigInteger('option_id')->unsigned();
            $table->foreign('option_id')->references('id')->on('shop_options')->onDelete('cascade');

            $table->bigInteger('option_value_id')->unsigned();
            $table->foreign('option_value_id')->references('id')->on('shop_option_values')->onDelete('cascade');

            $table->bigInteger('sku_id')->unsigned();
            $table->foreign('sku_id')->references('id')->on('shop_product_skus')->onDelete('cascade');

            $table->unique(['vendor', 'vendor_sku_value_id']);

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
        Schema::dropIfExists('shop_sku_values');
    }
};
