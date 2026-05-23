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
        Schema::table('auto_sms_devices', function (Blueprint $table) {
            $table->boolean('enable_spoof_detection')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_sms_devices', function (Blueprint $table) {
            $table->dropColumn('enable_spoof_detection');
        });
    }
};
