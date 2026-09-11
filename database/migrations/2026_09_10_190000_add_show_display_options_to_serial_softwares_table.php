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
                if (! Schema::hasColumn('serial_softwares', 'show_price')) {
                    $table->boolean('show_price')->default(true)->after('payment_instructions');
                }
                if (! Schema::hasColumn('serial_softwares', 'show_whatsapp')) {
                    $table->boolean('show_whatsapp')->default(true)->after('show_price');
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
                if (Schema::hasColumn('serial_softwares', 'show_whatsapp')) {
                    $table->dropColumn('show_whatsapp');
                }
                if (Schema::hasColumn('serial_softwares', 'show_price')) {
                    $table->dropColumn('show_price');
                }
            });
        }
    }
};
