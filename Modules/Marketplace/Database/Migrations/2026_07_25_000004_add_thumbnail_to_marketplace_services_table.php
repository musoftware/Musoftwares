<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_services')) {
            if (!Schema::hasColumn('marketplace_services', 'thumbnail')) {
                Schema::table('marketplace_services', function (Blueprint $table) {
                    $table->string('thumbnail')->nullable()->after('gallery');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_services') && Schema::hasColumn('marketplace_services', 'thumbnail')) {
            Schema::table('marketplace_services', function (Blueprint $table) {
                $table->dropColumn('thumbnail');
            });
        }
    }
};
