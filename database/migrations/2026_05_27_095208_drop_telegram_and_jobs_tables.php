<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('telegram_saved_messages');
        Schema::dropIfExists('telegram_user_sessions');
        Schema::dropIfExists('telegram_message_folders');
        Schema::dropIfExists('jobs_and_tasks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
