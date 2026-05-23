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
        Schema::table('whatsapp_channels', function (Blueprint $table) {
            $table->enum('channel_type', ['send_only', 'receive'])->default('send_only')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_channels', function (Blueprint $table) {
            $table->dropColumn('channel_type');
        });
    }
};
