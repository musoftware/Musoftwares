<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLikesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('like.likes_table', 'likes'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger(config('like.user_foreign_key', 'user_id'))->index()->comment('user_id');
            $table->morphs('likeable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists(config('like.likes_table', 'likes'));
    }
}
