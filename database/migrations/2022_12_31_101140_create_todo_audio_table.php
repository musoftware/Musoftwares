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
        Schema::create('todo_audio', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('todo_id')->unsigned();
            $table->foreign('todo_id')->references('id')->on('todos')->onDelete('cascade');

            $table->string('filename');
            $table->string('full_path');
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
        Schema::dropIfExists('todo_audio');
    }
};
