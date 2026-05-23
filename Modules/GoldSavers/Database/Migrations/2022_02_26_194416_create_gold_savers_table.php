<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoldSaversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gold_savers', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id', false, true)->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum('carat', ['14', '18', '21', '22', '24']);
            $table->float('gram_price', 9, 3);
            $table->float('grams', 9, 3);
            $table->float('tax', 9, 3);
            $table->float('additional_price', 9, 3);

            $table->boolean('zakat')->default(0);

            $table->dateTime('bought_date');

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
        Schema::dropIfExists('gold_savers');
    }
}
