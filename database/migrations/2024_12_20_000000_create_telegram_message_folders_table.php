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
        Schema::create('telegram_message_folders', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id', false, true)->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('folder_name', 255);
            $table->text('description')->nullable();

            $table->bigInteger('parent_folder_id', false, true)->nullable()->index();

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
        Schema::dropIfExists('telegram_message_folders');
    }
};
