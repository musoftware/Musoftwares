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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->string('post_image')->nullable();
            $table->string('post_video')->nullable();
            $table->string('post_audio')->nullable();
            $table->string('post_file')->nullable();
            $table->string('post_type')->nullable();
            $table->string('post_status')->nullable();
            $table->string('post_privacy')->nullable();
            $table->string('post_location')->nullable();
            $table->string('post_latitude')->nullable();
            $table->string('post_longitude')->nullable();
            $table->string('post_youtube')->nullable();
            $table->string('post_vimeo')->nullable();
            $table->string('post_dailymotion')->nullable();
            $table->string('post_facebook')->nullable();
            $table->string('post_metacafe')->nullable();
            $table->string('post_soundcloud')->nullable();
            $table->string('post_spotify')->nullable();

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
        Schema::dropIfExists('social_posts');
    }
};
