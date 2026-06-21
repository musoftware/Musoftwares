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
        Schema::create('todo_images', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('todo_id')->unsigned()->index()->nullable();
            $table->foreign('todo_id')->references('id')->on('todos')->onDelete('cascade');

            $table->string('filename');
            $table->boolean('default');

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
        Schema::dropIfExists('todo_images');
    }
};
