<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->longText('raw_payload')->nullable()->after('ai_response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropColumn('raw_payload');
        });
    }
};
