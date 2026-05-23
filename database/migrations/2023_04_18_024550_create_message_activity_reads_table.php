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
        Schema::create('message_activity_reads', function (Blueprint $table) {
            $table->id();

            $table->morphs('thread');

            $table->bigInteger('message_activity_id')->unsigned()->index();
            $table->foreign('message_activity_id')->references('id')->on('message_activities')->onDelete('cascade');

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('read')->default(0);
            $table->integer('heard')->default(0);

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
        Schema::dropIfExists('message_activity_reads');
    }
};
