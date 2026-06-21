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
        Schema::create('user_threads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user1_id')->nullable();
            $table->foreign('user1_id')->references('id')->on('users')->nullOnDelete();

            $table->unsignedBigInteger('user2_id')->nullable();
            $table->foreign('user2_id')->references('id')->on('users')->nullOnDelete();

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
        Schema::dropIfExists('user_threads');
    }
};
