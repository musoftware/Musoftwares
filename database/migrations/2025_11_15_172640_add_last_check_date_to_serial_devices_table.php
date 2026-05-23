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
        Schema::table('serial_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('serial_devices', 'last_check_date')) {
                $table->timestamp('last_check_date')->nullable()->after('current_ui_culture');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_devices', function (Blueprint $table) {
            if (Schema::hasColumn('serial_devices', 'last_check_date')) {
                $table->dropColumn('last_check_date');
            }
        });
    }
};
