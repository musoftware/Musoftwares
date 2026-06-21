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
        Schema::create('saved_replies', function (Blueprint $table) {
            $table->id();

            $table->morphs('user');

            $table->morphs('order');

            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('image', 512)->nullable();

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
        Schema::dropIfExists('saved_replies');
    }
};
