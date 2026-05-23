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
        Schema::create('files', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id', false, true)->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('filetype', 255)->nullable()->index();
            $table->string('url', 255);
            $table->bigInteger('size', false, true);


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
        Schema::dropIfExists('files');
    }
};
