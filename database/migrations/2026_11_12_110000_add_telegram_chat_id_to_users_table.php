<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The Telegram chat a user linked from their settings (Start in the site's bot), so the site can message them there. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('telegram_chat_id', 32)->nullable()->after('telegram_username'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('telegram_chat_id'));
        }
    }
};
