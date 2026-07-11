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
        Schema::create('currencies_exchanges', function (Blueprint $table) {
            $table->id();

            $table->date('date_string');

            $table->bigInteger('currency1')->unsigned()->index();
            $table->foreign('currency1')->references('id')->on('currencies')->onDelete('cascade');

            $table->bigInteger('currency2')->unsigned()->index();
            $table->foreign('currency2')->references('id')->on('currencies')->onDelete('cascade');

            $table->float('rate', 13, 6);

            $table->unique(['date_string', 'currency1', 'currency2']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies_exchanges');
    }
};
