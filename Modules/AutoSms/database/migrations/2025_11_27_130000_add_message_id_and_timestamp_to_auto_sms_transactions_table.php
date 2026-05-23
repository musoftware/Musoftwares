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
        Schema::table('auto_sms_transactions', function (Blueprint $table) {
            $table->string('message_id', 255)->nullable()->unique()->after('sms_message');
            $table->bigInteger('sms_timestamp')->nullable()->after('message_id');
            
            $table->index('message_id');
            $table->index('sms_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_sms_transactions', function (Blueprint $table) {
            $table->dropIndex(['sms_timestamp']);
            $table->dropIndex(['message_id']);
            $table->dropColumn(['message_id', 'sms_timestamp']);
        });
    }
};

