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
        Schema::create('file_folders', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id', false, true)->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('foldername', 255);

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
        Schema::dropIfExists('file_folders');
    }
};
