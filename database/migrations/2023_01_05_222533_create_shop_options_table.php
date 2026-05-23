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
        Schema::create('shop_options', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_option_id');

            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');

            $table->string('option_name');

            $table->unique(['vendor', 'vendor_option_id']);

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
        Schema::dropIfExists('shop_options');
    }
};
