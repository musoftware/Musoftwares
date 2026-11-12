<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The customer's choices in the Telegram bot: alerts stopped, and the bot's language. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_alerts_off')) {
                $table->boolean('telegram_alerts_off')->default(false)->after('telegram_chat_id');
            }
            if (! Schema::hasColumn('users', 'telegram_locale')) {
                $table->string('telegram_locale', 2)->nullable()->after('telegram_alerts_off');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['telegram_locale', 'telegram_alerts_off'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
