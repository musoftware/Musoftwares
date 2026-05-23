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
        Schema::create('shop_cities', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_city_id');

            $table->bigInteger('governorate_id')->unsigned();
            $table->foreign('governorate_id')->references('id')->on('shop_governorates')->onDelete('cascade');

            $table->string('city_name_ar', 255);
            $table->string('city_name_en', 255);

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
        Schema::dropIfExists('shop_cities');
    }
};
