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
        Schema::table('telegram_message_folders', function (Blueprint $table) {
            $table->foreign('parent_folder_id')->references('id')->on('telegram_message_folders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_message_folders', function (Blueprint $table) {
            $table->dropForeign(['parent_folder_id']);
        });
    }
}; 