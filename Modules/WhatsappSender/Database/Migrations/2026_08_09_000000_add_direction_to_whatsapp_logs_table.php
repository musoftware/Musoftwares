<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_logs', 'direction')) {
                $table->string('direction')->default('outbound')->after('status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_logs', 'direction')) {
                $table->dropColumn('direction');
            }
        });
    }
};
