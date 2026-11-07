<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_services', 'auto_complete')) {
                $table->boolean('auto_complete')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_services', 'auto_complete')) {
                $table->dropColumn('auto_complete');
            }
        });
    }
};
