<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tools')) {
            Schema::table('tools', function (Blueprint $table) {
                // Free tools are accessible to all authenticated users without a subscription
                $table->boolean('is_free')->default(false)->after('is_featured');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropColumn('is_free');
            });
        }
    }
};
