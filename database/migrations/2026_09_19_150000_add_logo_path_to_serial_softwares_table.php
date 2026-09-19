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
        if (Schema::hasTable('serial_softwares')) {
            Schema::table('serial_softwares', function (Blueprint ) {
                if (! Schema::hasColumn('serial_softwares', 'logo_path')) {
                    ->string('logo_path')->nullable()->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('serial_softwares')) {
            Schema::table('serial_softwares', function (Blueprint ) {
                if (Schema::hasColumn('serial_softwares', 'logo_path')) {
                    ->dropColumn('logo_path');
                }
            });
        }
    }
};
