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
        Schema::create('shop_governorates', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_governorate_id');

            $table->string('governorate_name_ar', 255);
            $table->string('governorate_name_en', 255);
            $table->boolean('unavailable')->default(0);

            $table->double('delivery', 8, 2)->default(60);


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
        Schema::dropIfExists('shop_governorates');
    }
};
