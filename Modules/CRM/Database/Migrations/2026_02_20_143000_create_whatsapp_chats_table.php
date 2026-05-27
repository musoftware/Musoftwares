<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_chats', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number'); // رقم العميل
            $table->string('session_name');  // اسم الجلسة في Musoftware
            $table->longText('user_message'); // الرسالة الأصلية (أو المجمعة)
            $table->longText('ai_response')->nullable(); // رد جيميناي
            $table->timestamps();
            
            $table->index('phone_number'); // لتسريع البحث عن تاريخ المحادثة
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_chats');
    }
}
