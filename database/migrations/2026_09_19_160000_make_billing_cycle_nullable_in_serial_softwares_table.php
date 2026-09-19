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
            Schema::table('serial_softwares', function (Blueprint $table) {
                if (Schema::hasColumn('serial_softwares', 'billing_cycle')) {
                    $table->string('billing_cycle', 30)->nullable()->default('lifetime')->change();
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
            Schema::table('serial_softwares', function (Blueprint $table) {
                if (Schema::hasColumn('serial_softwares', 'billing_cycle')) {
                    $table->string('billing_cycle', 30)->default('lifetime')->change();
                }
            });
        }
    }
};
