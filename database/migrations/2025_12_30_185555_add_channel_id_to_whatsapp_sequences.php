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
        Schema::table('whatsapp_sequences', function (Blueprint $table) {
            $table->foreignId('whatsapp_channel_id')->nullable()->constrained('whatsapp_channels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_sequences', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_channel_id']);
            $table->dropColumn('whatsapp_channel_id');
        });
    }
};
