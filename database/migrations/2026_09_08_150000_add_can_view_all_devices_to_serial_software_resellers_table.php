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
        if (Schema::hasTable('serial_software_resellers') && ! Schema::hasColumn('serial_software_resellers', 'can_view_all_devices')) {
            Schema::table('serial_software_resellers', function (Blueprint $table) {
                $table->boolean('can_view_all_devices')->default(false)->after('max_devices');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('serial_software_resellers') && Schema::hasColumn('serial_software_resellers', 'can_view_all_devices')) {
            Schema::table('serial_software_resellers', function (Blueprint $table) {
                $table->dropColumn('can_view_all_devices');
            });
        }
    }
};
