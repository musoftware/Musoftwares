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
        Schema::create('telegram_saved_messages', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id', false, true)->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('telegram_message_id')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_user_id')->nullable();

            $table->string('title', 255)->nullable();
            $table->text('content')->nullable();
            $table->string('file_type')->nullable(); // text, photo, video, document, audio, voice, etc.
            $table->string('file_url')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();

            $table->bigInteger('folder_id', false, true)->nullable()->index();
            $table->foreign('folder_id')->references('id')->on('telegram_message_folders')->onDelete('cascade');

            $table->timestamp('telegram_date')->nullable();
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
        Schema::dropIfExists('telegram_saved_messages');
    }
};
