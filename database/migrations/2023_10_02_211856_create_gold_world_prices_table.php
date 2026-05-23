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
        Schema::create('gold_world_prices', function (Blueprint $table) {
            $table->id();
            $table->dateTime('price_date');
            $table->decimal('price_14k', 10, 2);
            $table->decimal('price_18k', 10, 2);
            $table->decimal('price_21k', 10, 2);
            $table->decimal('price_22k', 10, 2);
            $table->decimal('price_24k', 10, 2);
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
        Schema::dropIfExists('gold_world_prices');
    }
};
