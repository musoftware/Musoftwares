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
        if (config('database.default') !== 'sqlite') {
            Schema::table('whatsapp_channels', function (Blueprint $table) {
                // Change qr_code from text to string to store file paths
                $table->string('qr_code')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('whatsapp_channels', function (Blueprint $table) {
                // Revert back to text field
                $table->text('qr_code')->nullable()->change();
            });
        }
    }
};
