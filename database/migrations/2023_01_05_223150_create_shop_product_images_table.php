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
        Schema::create('shop_product_images', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            $table->string('filename');
            $table->string('filename_540x600');
            $table->string('filename_150x160');
            $table->string('filename_810x900');
            $table->integer('main_image')->default(0);

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
        Schema::dropIfExists('shop_product_images');
    }
};
