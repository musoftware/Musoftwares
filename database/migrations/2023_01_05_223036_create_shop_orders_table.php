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
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_phone2')->nullable();
            $table->string('customer_email')->nullable();

            $table->string('customer_address');
            $table->string('customer_city')->nullable();
            $table->integer('customer_city_id')->nullable();
            $table->integer('customer_governorate_id')->nullable();
            $table->string('customer_governorate')->nullable();

            $table->double('commission', 8, 2)->default(0);
            $table->double('total', 8, 2)->default(0);

            $table->bigInteger('vendor_order_id')->nullable();

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
        Schema::dropIfExists('shop_orders');
    }
};
