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
        if (Schema::hasTable('serial_softwares') && ! Schema::hasColumn('serial_softwares', 'deleted_at')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        if (Schema::hasTable('serial_devices') && ! Schema::hasColumn('serial_devices', 'deleted_at')) {
            Schema::table('serial_devices', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        if (Schema::hasTable('serial_user_devices') && ! Schema::hasColumn('serial_user_devices', 'deleted_at')) {
            Schema::table('serial_user_devices', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('serial_softwares') && Schema::hasColumn('serial_softwares', 'deleted_at')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('serial_devices') && Schema::hasColumn('serial_devices', 'deleted_at')) {
            Schema::table('serial_devices', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('serial_user_devices') && Schema::hasColumn('serial_user_devices', 'deleted_at')) {
            Schema::table('serial_user_devices', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
