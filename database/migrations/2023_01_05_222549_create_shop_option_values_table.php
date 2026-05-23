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
        Schema::create('shop_option_values', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_option_value_id');

            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');

            $table->bigInteger('option_id')->unsigned();
            $table->foreign('option_id')->references('id')->on('shop_options')->onDelete('cascade');

            $table->string('value_name', 140);
            $table->unique(['product_id', 'option_id', 'value_name']);


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
        Schema::dropIfExists('shop_option_values');
    }
};
