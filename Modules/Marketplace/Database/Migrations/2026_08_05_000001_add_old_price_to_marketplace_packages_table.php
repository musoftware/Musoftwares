<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_packages', 'old_price')) {
                $table->decimal('old_price', 20, 8)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_packages', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_packages', 'old_price')) {
                $table->dropColumn('old_price');
            }
        });
    }
};
