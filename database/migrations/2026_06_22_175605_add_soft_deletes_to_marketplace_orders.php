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
        if (! Schema::hasColumn('marketplace_orders', 'deleted_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('marketplace_reviews', 'deleted_at')) {
            Schema::table('marketplace_reviews', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('marketplace_reviews', 'deleted_at')) {
            Schema::table('marketplace_reviews', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('marketplace_orders', 'deleted_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
