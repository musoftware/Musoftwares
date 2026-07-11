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
            $table->string('user_name')->nullable()->after('status');
            $table->string('user_domain')->nullable()->after('user_name');
            $table->string('machine_name')->nullable()->after('user_domain');
            $table->string('os_version')->nullable()->after('machine_name');
            $table->string('framework_version')->nullable()->after('os_version');
            $table->boolean('is_64bit_os')->nullable()->after('framework_version');
            $table->boolean('is_64bit_process')->nullable()->after('is_64bit_os');
            $table->text('current_directory')->nullable()->after('is_64bit_process');
            $table->string('current_culture')->nullable()->after('current_directory');
            $table->string('current_ui_culture')->nullable()->after('current_culture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_devices', function (Blueprint $table) {
            $table->dropColumn([
                'user_name',
                'user_domain',
                'machine_name',
                'os_version',
                'framework_version',
                'is_64bit_os',
                'is_64bit_process',
                'current_directory',
                'current_culture',
                'current_ui_culture',
            ]);
        });
    }
};
