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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->integer('expired_reminders_sent')->default(0)->after('auto_renew');
            $table->timestamp('last_expired_reminder_sent_at')->nullable()->after('expired_reminders_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['expired_reminders_sent', 'last_expired_reminder_sent_at']);
        });
    }
};
